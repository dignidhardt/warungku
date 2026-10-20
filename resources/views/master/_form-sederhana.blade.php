{{--
    Form tambah master data sederhana (satuan & satuan kemasan).

    Parameter:
    - $routeSimpan : nama route untuk POST
    - $judul       : label di tombol submit
    - $contoh      : contoh nilai untuk placeholder
--}}
<form action="{{ route($routeSimpan) }}" method="POST" class="baris-filter">
    @csrf

    <div class="bidang cari">
        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required
               placeholder="mis. {{ $contoh }}">
    </div>

    <button type="submit" class="tombol">{{ $judul }}</button>
</form>
