@echo off
rem Setup sekali-klik Warungku (Windows). Prasyarat: Docker Desktop.
cd /d "%~dp0"

where docker >nul 2>nul
if errorlevel 1 (
  echo Docker belum terpasang. Install Docker Desktop dulu:
  echo   https://www.docker.com/products/docker-desktop/
  echo Setelah itu buka Docker Desktop, lalu jalankan script ini lagi.
  pause
  exit /b 1
)

docker info >nul 2>nul
if errorlevel 1 (
  echo Docker Desktop belum jalan. Buka aplikasinya dulu, lalu jalankan lagi.
  pause
  exit /b 1
)

echo Menyalakan Warungku (pertama kali 1-2 menit: build + migrasi + seed)...
docker compose up -d

echo Menunggu aplikasi siap...
powershell -NoProfile -Command "for($i=0;$i -lt 60;$i++){try{Invoke-WebRequest -UseBasicParsing -Uri http://127.0.0.1:8010 | Out-Null; exit 0}catch{Start-Sleep 2}}; exit 1"
if errorlevel 1 echo Aplikasi belum merespon, cek: docker compose logs -f app

start http://127.0.0.1:8010
echo Warungku jalan di http://127.0.0.1:8010
echo Matikan: docker compose down
pause
