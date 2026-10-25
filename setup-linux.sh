#!/usr/bin/env bash
# Setup sekali-klik Warungku (Linux/macOS).
# Prasyarat: Docker Engine/Desktop + Compose. Script cek dan mengarahkan kalau belum ada.
set -e
cd "$(dirname "$0")"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker belum terpasang."
  echo "  Linux : https://docs.docker.com/engine/install/"
  echo "  macOS : https://www.docker.com/products/docker-desktop/"
  echo "Pasang dulu, lalu jalankan script ini lagi."
  read -rp "Tekan Enter untuk keluar..."
  exit 1
fi

echo "Menyalakan Warungku (pertama kali 1-2 menit: build + migrasi + seed)..."
docker compose up -d

echo "Menunggu aplikasi siap..."
for i in $(seq 1 60); do
  if curl -sf http://127.0.0.1:8010 >/dev/null 2>&1; then break; fi
  sleep 2
done

URL=http://127.0.0.1:8010
if command -v xdg-open >/dev/null 2>&1; then xdg-open "$URL"
elif command -v open >/dev/null 2>&1; then open "$URL"
else echo "Buka: $URL"; fi

echo "Warungku jalan di $URL (MySQL dev: 127.0.0.1:3309)."
echo "Matikan: docker compose down | Hapus data total: docker compose down -v"
