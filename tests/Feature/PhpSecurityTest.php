<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\PhpSecurity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHP2Shell (CVE-2026-109618 / CVE-2026-109619): admins are warned when the
 * server still runs an unpatched PHP release.
 */
class PhpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_patched_and_unpatched_releases(): void
    {
        $this->assertFalse(PhpSecurity::isPatched('8.4.26'));
        $this->assertTrue(PhpSecurity::isPatched('8.4.27'));
        $this->assertTrue(PhpSecurity::isPatched('8.4.30'));
        $this->assertFalse(PhpSecurity::isPatched('8.3.35'));
        $this->assertTrue(PhpSecurity::isPatched('8.3.36'));
        $this->assertFalse(PhpSecurity::isPatched('8.2.34'));
        $this->assertTrue(PhpSecurity::isPatched('8.2.35'));
        $this->assertFalse(PhpSecurity::isPatched('8.5.11'));
        $this->assertTrue(PhpSecurity::isPatched('8.5.12'));
        $this->assertTrue(PhpSecurity::isPatched('8.6.0'));

        // End-of-life branches never get the fix
        $this->assertFalse(PhpSecurity::isPatched('8.1.40'));
        $this->assertFalse(PhpSecurity::isPatched('7.4.33'));

        $this->assertSame('8.3.36', PhpSecurity::recommendedVersion('8.3.20'));
        $this->assertSame('8.4.27', PhpSecurity::recommendedVersion('7.4.33'));
    }

    public function test_only_admins_see_the_warning_on_an_unpatched_server(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('rahasia123'),
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
        ]);

        $page = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        if (PhpSecurity::isPatched()) {
            $page->assertDontSee('PHP2Shell');
        } else {
            $page->assertSee('PHP2Shell')->assertSee(PhpSecurity::recommendedVersion());
        }

        auth()->logout();
        $this->get('/login')->assertOk()->assertDontSee('PHP2Shell');
    }
}
