<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Services\AdminPasswordPolicy;
use Illuminate\Validation\Rule;

class OwnerUserRequest extends AdminMutationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Owner;
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;
        $name = $this->route()->getName();
        $base = ['reason' => 'nullable|string|max:500'];
        if ($name === 'admin.users.store') {
            $base += ['display_name' => 'required|string|min:2|max:100', 'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')], 'contact_email' => 'nullable|email|max:254', 'role' => ['required', Rule::in([...config('ruteaman_admin.admin_tiers'), 'student', 'verifier'])], 'school_id' => 'nullable|integer|exists:schools,id', 'permissions' => 'sometimes|array', 'permissions.*' => ['string', Rule::in(array_keys(config('ruteaman_permissions')))], 'creation_method' => ['required', Rule::in(['password_set', 'invite'])], 'initial_password' => ['exclude_unless:creation_method,password_set', 'required', 'string', 'max:128', 'confirmed', app(AdminPasswordPolicy::class)->rule()], 'must_change_password' => 'sometimes|boolean'];
        }
        if ($name === 'admin.users.update') {
            $base += ['display_name' => 'required|string|min:2|max:100', 'email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')->ignore($id)], 'contact_email' => 'nullable|email|max:254', 'school_id' => 'nullable|integer|exists:schools,id'];
        }
        if ($name === 'admin.users.role') {
            $base += ['role' => ['required', Rule::in([...config('ruteaman_admin.admin_tiers'), 'student', 'verifier'])], 'school_id' => 'nullable|integer|exists:schools,id'];
        }
        if ($name === 'admin.users.permissions') {
            $base += ['permissions' => 'present|array', 'permissions.*' => ['string', Rule::in(array_keys(config('ruteaman_permissions')))]];
        }
        if ($name === 'admin.users.password') {
            $base += ['initial_password' => ['required', 'string', 'max:128', 'confirmed', app(AdminPasswordPolicy::class)->rule()], 'must_change_password' => 'sometimes|boolean'];
        }
        if ($name === 'admin.users.destroy') {
            $base += ['confirmation' => 'required|string|max:254', 'delete_password' => 'required|string', 'permanent' => 'sometimes|boolean'];
        }

        return $base;
    }
}
