#!/usr/bin/env bash
# Makefile Warungku — pintasan operasi harian.
# Jalankan `make` untuk melihat daftar perintah.
.DEFAULT_GOAL := help
SHELL := /bin/bash

APP_DEV  := warungku-dev-app
APP_DEV_MYSQL := warungku-dev-mysql
APP_LIVE := warungku-live-app
URL_DEV  := http://127.0.0.1:8010
URL_LIVE := http://127.0.0.1:8011

.PHONY: help up down restart logs sh ps fresh seed test queue migrate status open db backup live-up live-down live-logs live-sh live-migrate live-build live-env clean restore backup-auto backup-list bersih

help: ## Tampilkan daftar perintah
	@echo "Warungku — perintah yang tersedia:"
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | \
		awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'
	@echo
	@echo "Dev  : $(URL_DEV)  (container $(APP_DEV))"
	@echo "Live : $(URL_LIVE) (container $(APP_LIVE))"

# ---------- DEVELOPMENT ----------
up: ## Nyalakan stack dev (app + mysql) lalu tampilkan status
	docker compose up -d --build
	@$(MAKE) --no-print-directory status

down: ## Matikan stack dev (data tetap tersimpan di volume)
	docker compose down

restart: ## Restart container app dev (setelah ubah .env.docker)
	docker compose up -d --force-recreate app

logs: ## Ikuti log app dev
	docker compose logs -f app

sh: ## Masuk shell container app dev
	docker exec -it $(APP_DEV) sh

ps: ## Status container dev
	docker compose ps

status: ## Cek kesehatan dev (HTTP + database)
	@printf "app  : "; curl -s -o /dev/null -w "%%{http_code}\n" $(URL_DEV)/login || echo "mati"
	@printf "mysql: "; docker exec warungku-dev-mysql mysqladmin ping -h localhost -uroot -prootsecret --silent 2>/dev/null || echo "mati"

open: ## Buka aplikasi dev di browser
	xdg-open $(URL_DEV) >/dev/null 2>&1 &

db: ## Buka konsol MySQL dev (host 127.0.0.1:3309, db warungku_dev)
	docker exec -it warungku-dev-mysql mysql -uwarungku -psecret warungku_dev

migrate: ## Jalankan migrasi yang belum jalan (dev)
	docker exec $(APP_DEV) php artisan migrate --force

fresh: ## HAPUS data dev lalu migrate + seed ulang (butuh konfirmasi)
	@read -p "Semua data DEV akan dihapus. Lanjut? [y/N] " ok; [ "$$ok" = "y" ] || exit 1
	docker exec $(APP_DEV) php artisan migrate:fresh --seed --force

bersih: ## KOSONGKAN data operasional (akun & master data tetap ada) — untuk uji alur dari nol
	@MYSQL_CONTAINER=$(APP_DEV_MYSQL) sh scripts/bersihkan-data.sh
	@echo "Akun admin & kasir tetap ada, begitu juga master data (kategori, satuan, supplier)."
	@echo "Data contoh produk hilang. Butuh data contoh lagi? Jalankan: make seed"

seed: ## Isi data contoh (dev)
	docker exec $(APP_DEV) php artisan db:seed --force

test: ## Jalankan test di database TERPISAH (warungku_test), dev tidak tersentuh
	@docker exec $(APP_DEV) sh -c 'php -r "exit(@fsockopen(getenv(\"DB_HOST\") ?: \"mysql\", 3306) ? 0 : 1);"' \
		|| (echo "MySQL belum siap."; exit 1)
	@docker exec warungku-dev-mysql mysql -uroot -prootsecret -e \
		"CREATE DATABASE IF NOT EXISTS warungku_test; GRANT ALL PRIVILEGES ON warungku_test.* TO 'warungku'@'%'; FLUSH PRIVILEGES;" 2>/dev/null || true
	docker exec \
		-e APP_ENV=testing \
		-e DB_DATABASE=warungku_test \
		-e CACHE_STORE=array \
		-e SESSION_DRIVER=array \
		-e QUEUE_CONNECTION=sync \
		$(APP_DEV) php artisan test

queue: ## Jalankan worker queue di dev (foreground)
	docker exec -it $(APP_DEV) php artisan queue:work

# ---------- LIVE ----------
live-env: ## Buat .env.live dari template kalau belum ada
	@test -f .env.live || (cp .env.live.example .env.live && echo "Dibuat .env.live — ISI APP_KEY dan DB_PASSWORD dulu!")
	@test -f .env.live && grep -q '^APP_KEY=base64:' .env.live || echo "PERINGATAN: APP_KEY di .env.live masih kosong."

live-up: live-env ## Nyalakan stack live
	docker compose --env-file .env.live -f compose.live.yaml up -d --build
	@printf "live : "; curl -s -o /dev/null -w "%%{http_code}\n" $(URL_LIVE)/login || echo "mati"

live-down: ## Matikan stack live (data tetap)
	docker compose --env-file .env.live -f compose.live.yaml down

live-logs: ## Ikuti log app live
	docker compose --env-file .env.live -f compose.live.yaml logs -f app

live-sh: ## Masuk shell container app live
	docker exec -it $(APP_LIVE) sh

live-migrate: ## Migrasi database live (manual, tanpa seed)
	docker compose --env-file .env.live -f compose.live.yaml exec app php artisan migrate --force

live-build: ## Rebuild image live setelah ada perubahan kode
	docker compose --env-file .env.live -f compose.live.yaml build app

backup: ## Dump database dev ke backups/
	@mkdir -p backups
	@# --no-tablespaces: user aplikasi tidak punya privilege PROCESS, dan
	@# tanpa flag ini mysqldump mencetak error "Access denied ... PROCESS"
	@# sambil tetap menulis file yang tampak berhasil.
	@docker exec warungku-dev-mysql mysqldump -uwarungku -psecret \
		--single-transaction --no-tablespaces --routines --triggers \
		--ssl-mode=DISABLED \
		warungku_dev 2>/dev/null > backups/warungku-dev-$$(date +%Y%m%d-%H%M%S).sql
	@ls -lh backups | tail -1

backup-auto: ## Jalankan backup terjadwal sekarang (container cron)
	@docker exec warungku-dev-backup /usr/local/bin/backup.sh
	@echo "Backup otomatis juga jalan sendiri setiap hari jam 1 pagi."

backup-list: ## Lihat daftar backup terjadwal (volume Docker)
	@docker exec warungku-dev-backup ls -lh /var/www/html/backups/ 2>/dev/null || \
		echo "Container backup belum jalan. Jalankan: make up"

restore: ## Pulihkan database dev dari file backup (FILE=backups/....sql)
	@test -n "$(FILE)" || (echo "Pakai: make restore FILE=backups/warungku-dev-XXXX.sql"; exit 1)
	@test -f "$(FILE)" || (echo "File tidak ada: $(FILE)"; exit 1)
	@read -p "Data dev akan DITIMPA dengan $(FILE). Lanjut? [y/N] " ok; [ "$$ok" = "y" ] || exit 1
	docker exec -i warungku-dev-mysql mysql -uwarungku -psecret warungku_dev < $(FILE)
	@echo "Restore selesai."

clean: ## Hapus container + volume dev (DATA DEV HILANG)
	@read -p "Container dan VOLUME dev akan dihapus. Lanjut? [y/N] " ok; [ "$$ok" = "y" ] || exit 1
	docker compose down -v
