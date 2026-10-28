<?php

namespace App\Http\Requests;

use App\Services\AdminPasswordPolicy;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AdminMutationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->role->admin();
    }

    public function rules(): array
    {
        $features = [];
        foreach (array_keys(config('ruteaman_admin.features')) as $key) {
            $features['features.'.$key] = ['sometimes', 'boolean'];
        }
        $base = ['permissions' => ['sometimes', 'array'], 'permissions.*' => ['string', Rule::in(array_keys(config('ruteaman_permissions')))], 'school_scope' => ['sometimes', 'array', 'max:1000'], 'school_scope.*' => [function ($attribute, $value, $fail) {
            if ($value !== '*' && ! (is_int($value) || (is_string($value) && ctype_digit($value)))) {
                $fail('Cakupan harus berupa ID sekolah atau *.');
            }
        }], 'school_id' => ['nullable', 'integer', 'exists:schools,id']];
        $name = $this->route()->getName();
        if (in_array($name, ['admin.admins.store', 'admin.schools.store'], true)) {
            $base += ['display_name' => ['required', 'string', 'min:2', 'max:100', Rule::notIn(config('ruteaman_admin.reserved'))], 'email' => ['required', 'email', 'max:254', 'unique:users,email'], 'contact_email' => ['required', 'email', 'max:254'], 'role' => ['required', Rule::in(config('ruteaman_admin.admin_tiers'))]];
        }
        if ($name === 'admin.admins.permissions') {
            $base['permissions'] = ['present', 'array'];
        }
        if ($name === 'admin.admins.tier') {
            $base['role'] = ['required', Rule::in(config('ruteaman_admin.admin_tiers'))];
        }
        if (in_array($name, ['admin.schools.store', 'admin.schools.update'], true)) {
            $required = $name === 'admin.schools.store' ? 'required' : 'sometimes';
            if($name==='admin.schools.store')$base['catalog_id']=['nullable','integer','exists:campus_catalog,id'];
            $base += ['institution_type'=>['sometimes',Rule::in($name==='admin.schools.update'?[$this->route('school')?->institution_type??'school']:['school','campus'])],'country_code'=>['sometimes','required','regex:/^[A-Z]{2}$/'],'campus_code'=>['nullable','string','max:64','not_regex:/[<>\r\n]/']];
            $base += ['name' => [$required, 'string', 'min:2', 'max:255'], 'public_slug' => [$required, 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:100', Rule::notIn(config('ruteaman_admin.reserved')), Rule::unique('schools', 'public_slug')->ignore($this->route('school')?->id)], 'latitude' => [$required, 'numeric', 'between:-90,90'], 'longitude' => [$required, 'numeric', 'between:-180,180'], 'catchment' => [$required, 'string', 'max:50000', 'json'], 'bmkg_area_code' => ['nullable', 'string', 'max:50'], 'timezone' => [$required, 'timezone'], 'address' => ['sometimes', 'nullable', 'string', 'max:500']];
        }
        if ($name === 'admin.schools.update') {
            foreach (['latitude', 'longitude'] as $key) {
                $base[$key] = ['required_with:catchment', 'numeric', $key === 'latitude' ? 'between:-90,90' : 'between:-180,180'];
            }
        }
        if (in_array($name, ['admin.schools.store', 'admin.schools.features', 'admin.features.all', 'admin.features.general'], true)) {
            $base += ['features' => ['required', 'array:'.implode(',', array_keys(config('ruteaman_admin.features')))], ...$features];
        }
        if (in_array($name, ['admin.admins.store', 'admin.schools.store'], true)) {
            $base += [
                'creation_method' => ['sometimes', Rule::in(['password_set', 'invite'])],
                'initial_password' => ['exclude_unless:creation_method,password_set', 'required', 'string', 'max:128', 'confirmed', app(AdminPasswordPolicy::class)->rule()],
                'must_change_password' => ['sometimes', 'boolean'],
            ];
        }

        if ($name === 'admin.features.general') {
            foreach (array_keys(config('ruteaman_admin.features')) as $key) {
                $base['features.'.$key] = ['required', 'boolean'];
            }
        }

        return $base;
    }

    protected function prepareForValidation(): void
    {
        if(is_string($this->input('country_code')))$this->merge(['country_code'=>strtoupper(trim($this->input('country_code')))]);
        foreach (['email', 'contact_email'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => mb_strtolower(trim($this->input($field)))]);
            }
        }if (! $this->has('permissions') && in_array($this->route()->getName(), ['admin.admins.permissions', 'admin.admins.store', 'admin.schools.store', 'admin.users.permissions'], true)) {
            $this->merge(['permissions' => []]);
        }
    }

    protected function failedValidation(Validator $v)
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['error' => ['code' => 'VALIDATION_FAILED', 'message' => 'Periksa isian yang ditandai.', 'field_errors' => $v->errors()], 'message' => 'Periksa isian.'], 422));
        }parent::failedValidation($v);
    }
}
