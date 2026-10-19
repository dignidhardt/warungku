<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutentikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_bisa_dibuka_tanpa_akun(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk', false);
    }

    public function test_root_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_halaman_terproteksi_menolak_tamu(): void
    {
        $this->get('/home')->assertRedirect('/login');
        $this->get('/transaksi')->assertRedirect('/login');
        $this->get('/stok')->assertRedirect('/login');
    }

    public function test_login_dengan_username_berhasil(): void
    {
        $user = User::factory()->create([
            'username' => 'budi',
            'password' => 'rahasia123',
        ]);

        $this->post('/login', [
            'identifier' => 'budi',
            'password' => 'rahasia123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_dengan_email_berhasil(): void
    {
        $user = User::factory()->create([
            'email' => 'budi@contoh.test',
            'password' => 'rahasia123',
        ]);

        $this->post('/login', [
            'identifier' => 'budi@contoh.test',
            'password' => 'rahasia123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_kata_sandi_salah_menampilkan_pesan_error(): void
    {
        User::factory()->create(['username' => 'budi', 'password' => 'rahasia123']);

        $response = $this->from('/login')->post('/login', [
            'identifier' => 'budi',
            'password' => 'salah',
        ]);

        // Pesan harus benar-benar tampil: key error HARUS 'identifier',
        // sesuai yang dibaca view. Key yang salah membuat pesan tak muncul.
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();

        $this->get('/login')->assertSee('salah', false);
    }

    public function test_kasir_tidak_bisa_membuka_manajemen_karyawan(): void
    {
        $kasir = User::factory()->create(['role' => User::ROLE_KASIR]);

        $this->actingAs($kasir)
            ->get('/users')
            ->assertRedirect(route('home'))
            ->assertSessionHas('error');
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_seeder_membuat_akun_admin_dan_kasir(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => 'kasir',
            'role' => User::ROLE_KASIR,
        ]);

        // Admin hasil seeder harus benar-benar bisa login.
        $this->post('/login', [
            'identifier' => 'admin',
            'password' => config('warungku.admin_password'),
        ])->assertRedirect(route('home'));
    }
}
