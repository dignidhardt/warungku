@if(session('error'))
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; font-family: sans-serif; font-weight: bold; border-left: 5px solid #dc3545;">
        
    {{ session('error') }}
    </div>
@endif
@extends('layouts.app')

@section('content')
<style>
    .card, 
    .card-body,
    div[class*="container"], 
    div[class*="col"] {
        max-width: 100% !important;
        width: 100% !important;
    }
</style>

<div style="width: 100%; padding-right: 30px; box-sizing: border-box; margin-bottom: 30px;">
    
    <div style="margin-bottom: 20px;">
    </div>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        
        <div style="flex: 1; min-width: 350px;">
            <div class="card" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); box-sizing: border-box;">
                <h5 style="margin-top: 0; margin-bottom: 20px; font-family: sans-serif; color: #444; border-bottom: 1px solid #eee; padding-bottom: 10px;">Pilih Produk</h5>
                
                <form id="form-tambah-keranjang">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold; font-family: sans-serif; font-size: 14px;">Nama Produk / Barcode</label>
                        <input type="text" id="pilih_produk" list="produk_list" autocomplete="off" required placeholder="Ketik nama atau scan barcode..." style="width:100%; height:40px; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
                        
                        <datalist id="produk_list">
                            @foreach($produk as $p)
                                <option value="{{ $p->nama_produk }} (Barcode: {{ $p->barcode }})" data-id="{{ $p->id }}" data-nama="{{ $p->nama_produk }}" data-harga="{{ $p->harga_jual }}" data-stok="{{ $p->stok }}"></option>
                            @endforeach
                        </datalist>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display:block; margin-bottom:5px; font-weight:bold; font-family: sans-serif; font-size: 14px;">Jumlah (Qty)</label>
                        <input type="number" id="jumlah_beli" value="1" min="1" required style="width:100%; height:40px; padding:10px; border:1px solid #ccc; border-radius:5px; box-sizing: border-box;">
                    </div>

                    <button type="button" id="btn-tambah" style="width: 100%; background: #0d6efd; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-family: sans-serif; font-size: 14px;">
                        + Tambahkan ke Keranjang
                    </button>
                </form>
            </div>
        </div>

        <div style="flex: 2; min-width: 500px;">
            <div class="card" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,.05); box-sizing: border-box;">
                
                <div style="background: #212529; color: #0dfd7d; padding: 20px; border-radius: 5px; text-align: right; margin-bottom: 20px;">
                    <span style="font-family: sans-serif; font-size: 14px; color: #aaa; display: block; margin-bottom: 5px;">GRAND TOTAL</span>
                    <strong style="font-family: monospace; font-size: 32px;" id="grand-total-text">Rp 0</strong>
                </div>

                <h5 style="margin-top: 0; margin-bottom: 15px; font-family: sans-serif; color: #444;">Keranjang Belanja</h5>
                <div style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 14px;" id="tabel-keranjang">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #dee2e6;">
                                <th style="padding: 10px; text-align: center; width: 40px;">No</th>
                                <th style="padding: 10px; text-align: left;">Nama Produk</th>
                                <th style="padding: 10px; text-align: right; width: 120px;">Harga</th>
                                <th style="padding: 10px; text-align: center; width: 80px;">Qty</th>
                                <th style="padding: 10px; text-align: right; width: 130px;">Subtotal</th>
                                <th style="padding: 10px; text-align: center; width: 60px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="row-kosong">
                                <td colspan="6" style="padding: 20px; text-align: center; color: #888; font-style: italic;">
                                    Belum ada produk di keranjang.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form id="form-proses-transaksi">
                    @csrf
                    <input type="hidden" name="keranjang" id="input-keranjang-json">
                    <input type="hidden" name="grand_total" id="input-grand-total">
                    <input type="hidden" name="kembali" id="input-kembali">

                    <div style="border-top: 1px solid #eee; padding-top: 20px; display: flex; justify-content: flex-end;">
                        <div style="width: 300px;">
                            <div style="margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <label style="font-weight: bold; font-family: sans-serif; font-size: 14px;">Bayar (Cash)</label>
                                <input type="number" id="input-bayar" name="bayar" required placeholder="Rp 0" style="width: 180px; height: 35px; padding: 5px 10px; border: 1px solid #ccc; border-radius: 4px; text-align: right;">
                            </div>
                            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: bold; font-family: sans-serif; font-size: 14px;">Kembali</span>
                                <strong style="font-family: sans-serif; font-size: 16px; color: red;" id="text-kembali">Rp 0</strong>
                            </div>
                            <button type="submit" style="width: 100%; background: #198754; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-family: sans-serif; font-size: 15px;">
                                Selesai & Cetak Struk
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>

<script>
    let keranjang = [];
    let grandTotal = 0;

    document.getElementById('btn-tambah').addEventListener('click', function() {
        const inputProduk = document.getElementById('pilih_produk').value;
        const qty = parseInt(document.getElementById('jumlah_beli').value) || 1;
        
        const options = document.querySelectorAll('#produk_list option');
        let produkDitemukan = null;

        options.forEach(opt => {
            if (opt.value === inputProduk) {
                produkDitemukan = {
                    id: opt.getAttribute('data-id'),
                    nama: opt.getAttribute('data-nama'),
                    harga: parseInt(opt.getAttribute('data-harga')),
                    stok: parseInt(opt.getAttribute('data-stok'))
                };
            }
        });

        if (!produkDitemukan) {
            alert('Produk tidak valid! Silakan pilih dari daftar.');
            return;
        }

        if (qty > produkDitemukan.stok) {
            alert('Stok tidak mencukupi! Sisa stok: ' + produkDitemukan.stok);
            return;
        }

        let sisaItem = keranjang.find(item => item.id === produkDitemukan.id);
        if (sisaItem) {
            if ((sisaItem.qty + qty) > produkDitemukan.stok) {
                alert('Total jumlah melebihi stok yang tersedia!');
                return;
            }
            sisaItem.qty += qty;
            sisaItem.subtotal = sisaItem.qty * sisaItem.harga;
        } else {
            keranjang.push({
                id: produkDitemukan.id,
                nama: produkDitemukan.nama,
                harga: produkDitemukan.harga,
                qty: qty,
                subtotal: qty * produkDitemukan.harga
            });
        }

        document.getElementById('pilih_produk').value = '';
        document.getElementById('jumlah_beli').value = 1;

        renderKeranjang();
    });

    function renderKeranjang() {
        const tbody = document.querySelector('#tabel-keranjang tbody');
        tbody.innerHTML = '';
        grandTotal = 0;

        if (keranjang.length === 0) {
            tbody.innerHTML = `
                <tr class="row-kosong">
                    <td colspan="6" style="padding: 20px; text-align: center; color: #888; font-style: italic;">
                        Belum ada produk di keranjang.
                    </td>
                </tr>`;
            updateTotal();
            return;
        }

        keranjang.forEach((item, index) => {
            grandTotal += item.subtotal;
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #eee';
            tr.innerHTML = `
                <td style="padding: 10px; text-align: center;">${index + 1}</td>
                <td style="padding: 10px;">${item.nama}</td>
                <td style="padding: 10px; text-align: right;">Rp ${item.harga.toLocaleString('id-ID')}</td>
                <td style="padding: 10px; text-align: center;">${item.qty}</td>
                <td style="padding: 10px; text-align: right;">Rp ${item.subtotal.toLocaleString('id-ID')}</td>
                <td style="padding: 10px; text-align: center;">
                    <button type="button" onclick="hapusItem('${item.id}')" style="background: red; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">Hapus</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        updateTotal();
    }

    function hapusItem(id) {
        keranjang = keranjang.filter(item => item.id !== id);
        renderKeranjang();
    }

    function updateTotal() {
        document.getElementById('grand-total-text').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
        hitungKembalian();
    }

    document.getElementById('input-bayar').addEventListener('input', hitungKembalian);

    function hitungKembalian() {
        const bayar = parseInt(document.getElementById('input-bayar').value) || 0;
        const kembali = bayar - grandTotal;
        const textKembali = document.getElementById('text-kembali');

        if (kembali >= 0) {
            textKembali.innerText = 'Rp ' + kembali.toLocaleString('id-ID');
            textKembali.style.color = 'green';
        } else {
            textKembali.innerText = 'Rp 0';
            textKembali.style.color = 'red';
        }
    }

    // EVENT LISTENER BARU: MENGIRIM DATA TRANSAKSI KE CONTROLLER & LAPORAN
    document.getElementById('form-proses-transaksi').addEventListener('submit', function(e) {
        e.preventDefault();

        if (keranjang.length === 0) {
            alert('Keranjang masih kosong!');
            return;
        }

        const bayar = parseInt(document.getElementById('input-bayar').value) || 0;
        if (bayar < grandTotal) {
            alert('Uang pembayaran masih kurang!');
            return;
        }

        // Ikat nilai ke input hidden sebelum submit
        document.getElementById('input-keranjang-json').value = JSON.stringify(keranjang);
        document.getElementById('input-grand-total').value = grandTotal;
        document.getElementById('input-kembali').value = bayar - grandTotal;

        // Mengirimkan data via Ajax
        fetch("{{ route('transaksi.store') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
                "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
            },
            body: new URLSearchParams(new FormData(this)).toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Transaksi Berhasil! Data penjualan otomatis tercatat di Laporan.');
                window.location.reload(); 
            } else {
                alert('Gagal memproses transaksi: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan sistem saat menyimpan transaksi.');
        });
    });
</script>
@endsection