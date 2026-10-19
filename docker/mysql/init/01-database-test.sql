-- Dijalankan MySQL SEKALI saat volume pertama kali dibuat.
--
-- Tujuan: database khusus test tersedia sejak awal, sehingga `make test`
-- tidak perlu langkah manual. Dipisah dari `warungku_dev` karena
-- RefreshDatabase menghapus seluruh tabel pada database yang terhubung.

CREATE DATABASE IF NOT EXISTS warungku_test
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- User aplikasi butuh akses penuh ke database test-nya sendiri.
GRANT ALL PRIVILEGES ON warungku_test.* TO 'warungku'@'%';
FLUSH PRIVILEGES;
