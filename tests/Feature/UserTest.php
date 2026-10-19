<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bisa_membuat_akun_kasir(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Budi Kasir',
            'username' => 'budi',
            'email' => 'budi@warungku.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => User::ROLE_KASIR,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'budi',
            'role' => User::ROLE_KASIR,
        ]);

        // Akun baru harus benar-benar bisa login.
        $this->post('/login', [
            'identifier' => 'budi',
            'password' => 'rahasia123',
        ])->assertRedirect(route('home'));
    }

    public function test_halaman_edit_karyawan_tersedia(): void
    {
        $admin = User::factory()->admin()->create();
        $kasir = User::factory()->create(['name' => 'Kasir Lama']);

        $this->actingAs($admin)
            ->get(route('users.edit', $kasir))
            ->assertOk()
            ->assertSee('Kasir Lama');
    }

    public function test_admin_bisa_mengubah_data_karyawan_tanpa_mengganti_sandi(): void
    {
        $admin = User::factory()->admin()->create();
        $kasir = User::factory()->create(['name' => 'Nama Lama', 'username' => 'lama']);
        $sandiLama = $kasir->password;

        $this->actingAs($admin)->put(route('users.update', $kasir), [
            'name' => 'Nama Baru',
            'username' => 'baru',
            'email' => $kasir->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => User::ROLE_KASIR,
        ])->assertRedirect(route('users.index'));

        $kasir->refresh();
        $this->assertSame('Nama Baru', $kasir->name);
        $this->assertSame('baru', $kasir->username);
        $this->assertSame($sandiLama, $kasir->password, 'Sandi tidak boleh berubah saat field kosong.');
    }

    public function test_admin_bisa_mengganti_sandi_karyawan(): void
    {
        $admin = User::factory()->admin()->create();
        $kasir = User::factory()->create(['username' => 'karyawan']);

        $this->actingAs($admin)->put(route('users.update', $kasir), [
            'name' => $kasir->name,
            'username' => 'karyawan',
            'email' => $kasir->email,
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
            'role' => User::ROLE_KASIR,
        ])->assertRedirect(route('users.index'));

        $this->post('/logout');

        $this->post('/login', [
            'identifier' => 'karyawan',
            'password' => 'sandibaru123',
        ])->assertRedirect(route('home'));
    }

    public function test_username_ganda_ditolak(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['username' => 'dipakai']);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Baru',
            'username' => 'dipakai',
            'email' => 'baru@warungku.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => User::ROLE_KASIR,
        ])->assertSessionHasErrors('username');
    }

    public function test_admin_tidak_bisa_menurunkan_peran_sendiri(): void
    {
        $admin = User::factory()->admin()->create(['username' => 'saya']);

        $this->actingAs($admin)->put(route('users.update', $admin), [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => User::ROLE_KASIR,
        ])->assertSessionHas('error');

        $this->assertSame(User::ROLE_ADMIN, $admin->refresh()->role);
    }

    public function test_admin_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_terakhir_tidak_bisa_dihapus(): void
    {
        $admin = User::factory()->admin()->create();
        $adminLain = User::factory()->admin()->create();

        // Dengan dua admin, satu boleh dihapus.
        $this->actingAs($admin->fresh())
            ->delete(route('users.destroy', $adminLain))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $adminLain->id]);
    }
}
