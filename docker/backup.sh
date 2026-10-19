#!/bin/sh
#
# Backup database terjadwal.
#
# Dipakai oleh service `backup` di compose.yaml. Dijalankan sebagai cron
# di dalam container app, bukan dari host, supaya backup tetap jalan
# walau tidak ada satu pun terminal yang terbuka.
#
# Berapa lama backup disimpan: 14 hari. File yang lebih lama dihapus.
# Tanpa batas ini, folder backup akan tumbuh tanpa henti.

set -eu

RETENSI_HARI="${RETENSI_HARI:-14}"
DESTINASI="${DESTINASI:-/var/www/html/backups}"
KREDENSIAL="${MYSQL_USER:-warungku}:${MYSQL_PASSWORD:-secret}"
DATABASE="${DB_DATABASE:-warungku_dev}"
HOST="${DB_HOST:-mysql}"

mkdir -p "$DESTINASI"

NAMA="warungku-$(basename "$DATABASE")-$(date +%Y%m%d-%H%M%S).sql"
BERKAS="$DESTINASI/$NAMA"

# --no-tablespaces: user aplikasi tidak punya privilege PROCESS, dan tanpa
# flag ini mysqldump mencetak error "Access denied" sambil tetap menulis
# file yang tampak berhasil.
#
# --ssl-mode=DISABLED: MySQL 8.4 memakai sertifikat self-signed, dan client
# menolak handshake dengan "TLS/SSL error: self-signed certificate in chain".
# Koneksinya lokal di jaringan Docker, jadi tidak perlu TLS.
if ! mysqldump \
    --host="$HOST" \
    --user="${KREDENSIAL%%:*}" \
    --password="${KREDENSIAL#*:}" \
    --single-transaction \
    --no-tablespaces \
    --routines \
    --triggers \
    --ssl-mode=DISABLED \
    "$DATABASE" > "$BERKAS" 2>/dev/null
then
    echo "[backup] GAGAL membuat backup $DATABASE"
    rm -f "$BERKAS"
    exit 1
fi

# File kosong berarti dump-nya gagal walau exit code-nya 0.
if [ ! -s "$BERKAS" ]; then
    echo "[backup] GAGAL: file backup kosong"
    rm -f "$BERKAS"
    exit 1
fi

echo "[backup] OK: $NAMA ($(du -h "$BERKAS" | cut -f1))"

# Hapus backup lama.
find "$DESTINASI" -name 'warungku-*.sql' -type f -mtime "+${RETENSI_HARI}" -delete 2>/dev/null || true

jumlah=$(find "$DESTINASI" -name 'warungku-*.sql' -type f 2>/dev/null | wc -l)
echo "[backup] Total backup tersimpan: $jumlah berkas (retensi ${RETENSI_HARI} hari)"
