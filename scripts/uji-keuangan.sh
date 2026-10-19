#!/bin/bash
# Uji alur fitur keuangan, pembelian, retur, dan tutup buku lewat HTTP.
# Dijalankan dari host, mengetuk aplikasi dev di 127.0.0.1:8010.

set -u
BASE="${BASE:-http://127.0.0.1:8010}"
JAR=$(mktemp)
GOK=0; GAGAL=0

cek() {
    # cek <label> <expected> <actual>
    if [ "$2" = "$3" ]; then
        echo "  OK   $1 ($3)"; GOK=$((GOK+1))
    else
        echo "  GAGAL $1 (harapan $2, dapat $3)"; GAGAL=$((GAGAL+1))
    fi
}

# Token CSRF diambil dari <meta name="csrf-token">, bukan dari form.
#
# Alasannya: setelah login, /login sudah tidak bisa diakses (middleware guest
# mengarahkan ke /home), jadi tidak ada lagi input name="_token" di sana.
# Meta tag ada di setiap halaman lewat layout, jadi andal untuk semua request.
token() {
    curl -s -c "$JAR" -b "$JAR" "$BASE/home" \
        | grep -o 'name="csrf-token" content="[^"]*"' | head -1 \
        | sed 's/.*content="//;s/"//'
}

# POST dengan token yang valid: ambil halaman dulu (token + cookie), lalu kirim.
#
# Semua argumen diproses sebagai data "key=value". Kalau ada yang lolos tanpa
# prefiks --data-urlencode, curl akan menganggapnya sebagai URL tambahan,
# lalu menulis "000" untuk URL yang gagal — hasilnya "302000" dan tidak
# bisa dibandingkan.
post() {
    local path="$1"; shift
    local t
    t=$(token)
    local args=()
    local item
    for item in "$@"; do
        args+=(--data-urlencode "$item")
    done
    curl -s -b "$JAR" -c "$JAR" -o /dev/null -w "%{http_code}" \
        -X POST "$BASE$path" --data-urlencode "_token=$t" "${args[@]}"
}

# Login harus pakai halaman /login (hanya di sana ada form token).
login() {
    local t
    t=$(curl -s -c "$JAR" -b "$JAR" "$BASE/login" \
        | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
    curl -s -b "$JAR" -c "$JAR" -o /dev/null -w "%{http_code}" -X POST "$BASE/login" \
        --data-urlencode "_token=$t" --data-urlencode "identifier=$1" --data-urlencode "password=$2"
}

kode() { curl -s -b "$JAR" -c "$JAR" -o /dev/null -w "%{http_code}" "$BASE$1"; }
kode_post() { curl -s -b "$JAR" -c "$JAR" -o /dev/null -w "%{http_code}" -X POST "$BASE$1" -d "$2"; }

echo "=== 1. LOGIN ADMIN ==="
cek "login admin" "302" "$(login admin admin12345)"
cek "home setelah login" "200" "$(kode /home)"

echo "=== 2. HALAMAN KEUANGAN ==="
for p in /keuangan /keuangan/laba-rugi /keuangan/buku-kas /keuangan/pengeluaran /tutup-buku /pembelian /pembelian/create; do
    cek "GET $p" "200" "$(kode $p)"
done

echo "=== 3. CATAT PENGELARAN OPERASIONAL ==="
cek "simpan pengeluaran" "302" "$(post /keuangan/pengeluaran \
    'jenis=operasional' 'kategori=Listrik & air' "tanggal=$(date +%F)" 'jumlah=250000' 'keterangan=token listrik')"

echo "=== 4. CATAT PRIVE (tidak boleh jadi biaya) ==="
cek "simpan prive" "302" "$(post /keuangan/pengeluaran \
    'jenis=prive' 'kategori=Ambil uang pribadi' "tanggal=$(date +%F)" 'jumlah=100000' 'keterangan=prive')"

echo "=== 5. CATAT PEMBELIAN ==="
# Pilih produk yang stoknya tersedia. Halaman /stok diurutkan stok menaik,
# jadi produk pertama belum tentu yang stoknya bisa dipakai.
PID=$(docker exec warungku-dev-mysql mysql -uwarungku -psecret warungku_dev -N -B \
    -e "select id from stok where stok > 5 order by id limit 1" 2>/dev/null)
STOK_AWAL=$(docker exec warungku-dev-mysql mysql -uwarungku -psecret warungku_dev -N -B \
    -e "select stok from stok where id=$PID" 2>/dev/null)
if [ -z "$PID" ] || [ -z "$STOK_AWAL" ]; then
    echo "  GAGAL tidak ada produk dengan stok cukup"; GAGAL=$((GAGAL+1))
else
    echo "  (produk id=$PID, stok awal=$STOK_AWAL)"
    cek "simpan pembelian" "302" "$(post /pembelian \
        'supplier=Toko Besar' "tanggal=$(date +%F)" \
        "items[0][id]=$PID" 'items[0][qty]=5' 'items[0][harga_beli]=2000')"
fi

echo "=== 6. TRANSAKSI DENGAN DISKON + QRIS ==="
T=$(token)
R=$(curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/transaksi" -H "Accept: application/json" \
    --data-urlencode "_token=$T" \
    --data-urlencode "keranjang=[{\"id\":$PID,\"qty\":1}]" \
    --data-urlencode "bayar=999999" --data-urlencode "diskon=500" \
    --data-urlencode "metode_bayar=qris")
echo "  response: $R"

echo "=== 7. TRANSAKSI TUNAI BIASA ==="
T=$(token)
R2=$(curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/transaksi" -H "Accept: application/json" \
    --data-urlencode "_token=$T" \
    --data-urlencode "keranjang=[{\"id\":$PID,\"qty\":2}]" \
    --data-urlencode "bayar=100000" --data-urlencode "metode_bayar=tunai")
echo "  response: $R2"

echo "=== 8. TUTUP BUKU ==="
# Penting: cek bukan cuma kode HTTP, tapi juga baris yang benar-benar
# tersimpan di database. Redirect 302 juga terjadi kalau validasi gagal
# (Laravel mengembalikan "back()"), jadi status code saja tidak cukup bukti.
TUTUP_SEBELUM=$(docker exec warungku-dev-mysql mysql -uwarungku -psecret warungku_dev -N -B \
    -e "select count(*) from tutup_bukus" 2>/dev/null)
cek "simpan tutup buku" "302" "$(post /tutup-buku \
    "tanggal=$(date +%F)" 'kas_fisik=50000' 'catatan=uji selisih')"
TUTUP_SESUDAH=$(docker exec warungku-dev-mysql mysql -uwarungku -psecret warungku_dev -N -B \
    -e "select count(*) from tutup_bukus" 2>/dev/null)
cek "baris tutup buku bertambah" "1" \
    "$(( ${TUTUP_SESUDAH:-0} - ${TUTUP_SEBELUM:-0} ))"
cek "catatan tersimpan" "uji selisih" \
    "$(docker exec warungku-dev-mysql mysql -uwarungku -psecret warungku_dev -N -B \
        -e "select catatan from tutup_bukus order by id desc limit 1" 2>/dev/null)"

echo "=== 8b. TUTUP BUKU TANPA CATATAN (selisih ≠ 0 harus ditolak) ==="
cek "tolak tutup buku tanpa catatan" "302" "$(post /tutup-buku \
    "tanggal=$(date -d yesterday +%F)" 'kas_fisik=12345')"
cek "tidak ada baris baru" "0" \
    "$(docker exec warungku-dev-mysql mysql -uwarungku -psecret warungku_dev -N -B \
        -e "select count(*) from tutup_bukus where tanggal = '$(date -d yesterday +%F)'" 2>/dev/null)"

echo "=== 9. HALAMAN MASIH 200 SETELAH SEMUA DIATAS ==="
for p in /keuangan /keuangan/laba-rugi /keuangan/buku-kas /tutup-buku /pembelian; do
    cek "GET $p" "200" "$(kode $p)"
done

rm -f "$JAR"
echo ""
echo "LULUS: $GOK | GAGAL: $GAGAL"
[ "$GAGAL" -eq 0 ]
