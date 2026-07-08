<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TOKO KELONTONG</title>

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#f4f6f9;
}

/* Navbar */
.navbar{
    width:100%;
    height:60px;
    background:#0d6efd;
    color:white;
    display:flex;
    align-items:center;
    justify-content:between; /* Mengatur ruang kiri dan kanan */
    padding:0 20px;
}

.nav-left {
    display: flex;
    align-items: center;
}

.nav-right {
    margin-left: auto; /* Memaksa nama admin bergeser ke kanan */
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Tombol hamburger */
.menu-btn{
    font-size:28px;
    cursor:pointer;
    margin-right:20px;
}

/* Layout */
.container{
    display:flex;
}

/* Sidebar */
.sidebar{
    width:200px;
    background:#1e293b;
    color:white;
    min-height:100vh;
    transition:0.3s;
    overflow:hidden;
}

/* Sidebar tertutup */
.sidebar.hide{
    width:0;
}

/* Judul */
.sidebar h2{
    text-align:center;
    padding:20px 0;
}

/* Menu */
.sidebar a, .sidebar button{
    display:block;
    width: 100%;
    color:white;
    text-decoration:none;
    padding:15px 20px;
    text-align: left;
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
}

.sidebar a:hover, .sidebar button:hover{
    background:#334155;
}

/* Isi */
.content{
    flex:1;
    padding:30px;
    transition:0.3s;
}

.card{
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,.1);
}
</style>

</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <span class="menu-btn" onclick="toggleSidebar()">☰</span>
        <h2>TOKO KELONTONG</h2>
    </div>

    <div class="nav-right">
        @auth
            <span style="font-weight: bold;">{{ Auth::user()->name }}</span>
        @endauth
    </div>
</div>

<div class="container">

    <div class="sidebar" id="sidebar">

        <h2>Menu</h2>

        <a href="{{ route('home') }}">Home</a>
        <hr style="border-color: #334155;"></hr>
        
        <a href="{{ route('stok.index') }}">Stok</a>
        <hr style="border-color: #334155;"></hr>
        
        <a href="{{ route('transaksi.index') }}">Transaksi</a>
        <hr style="border-color: #334155;"></hr>
        
        <a href="{{ route('laporan.index') }}">Laporan</a>
        <hr style="border-color: #334155;"></hr>

        @if(Auth::user() && Auth::user()->role === 'admin')
            <a href="{{ route('users.index') }}">Manajemen Karyawan</a>
            <hr style="border-color: #334155;"></hr>
        @endif
        
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" style="color: #ef4444; font-weight: bold;"> Keluar</button>
        </form>

    </div>

    @yield('content')

</div>

<script>
function toggleSidebar(){
    document.getElementById("sidebar").classList.toggle("hide");
}
</script>

</body>
</html>