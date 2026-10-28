<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdminUserDeleteDialogTest extends TestCase
{
    private function renderDialog(array $options = []): string
    {
        $viewer = (new User)->forceFill(['id' => 1, 'role' => 'owner']);
        $target = (new User)->forceFill(['id' => 2, 'display_name' => 'Target Fixture', 'email' => 'target@fixture.invalid', 'role' => 'student']);

        return view('admin.users.delete-dialog', array_replace(['viewer' => $viewer, 'target' => $target, 'canPermanent' => false, 'deleteToken' => 'SyntheticPageProof', 'errors' => new ViewErrorBag], $options))->render();
    }

    public function test_dialog_uses_page_identity_and_only_cancel_continue_actions(): void
    {
        $html = $this->renderDialog();
        $this->assertStringContainsString('data-user-delete-open', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringContainsString('name="delete_token"', $html);
        $this->assertStringContainsString('Batalkan', $html);
        $this->assertStringContainsString('Lanjutkan', $html);
        $this->assertStringNotContainsString('name="confirmation"', $html);
        $this->assertStringNotContainsString('name="delete_password"', $html);
        $this->assertStringNotContainsString('data-sensitive', $html);
    }

    public function test_own_account_does_not_show_delete_button(): void
    {
        $target = (new User)->forceFill(['id' => 1, 'role' => 'owner']);
        $this->assertStringNotContainsString('data-user-delete-open', $this->renderDialog(['target' => $target]));
    }

    public function test_non_owner_does_not_show_delete_button(): void
    {
        $viewer = (new User)->forceFill(['id' => 1, 'role' => 'school_admin']);
        $this->assertStringNotContainsString('data-user-delete-open', $this->renderDialog(['viewer' => $viewer]));
    }

    public function test_quick_dialog_never_offers_permanent_deletion(): void
    {
        $this->assertStringNotContainsString('name="permanent"', $this->renderDialog(['canPermanent' => true]));
    }

    public function test_error_reopens_dialog_without_password_or_name_input(): void
    {
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['delete_token' => ['Muat ulang halaman.']]));
        $html = $this->renderDialog(['errors' => $errors]);
        $this->assertStringContainsString('data-delete-reopen="1"', $html);
        $this->assertStringContainsString('Muat ulang halaman.', $html);
        $this->assertStringNotContainsString('type="password"', $html);
    }

    public function test_target_identity_is_html_escaped(): void
    {
        $target = (new User)->forceFill(['id' => 2, 'display_name' => '<script>alert(1)</script>', 'email' => 'fixture@example.invalid']);
        $html = $this->renderDialog(['target' => $target]);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_delete_component_precedes_profile_form_and_bottom_panel_is_removed(): void
    {
        $source = file_get_contents(resource_path('views/admin/users/form.blade.php'));
        $this->assertLessThan(strpos($source, '<form class="admin-panel"'), strpos($source, "@include('admin.users.delete-dialog')"));
        $this->assertStringNotContainsString('<section class="admin-panel"><h2>Hapus akun</h2>', $source);
    }
}
