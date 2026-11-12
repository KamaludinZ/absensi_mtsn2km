<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Super App ID: the "id" of a user in Super App (a UUID), stored on the GTK
 * (employees) and student records so SSO logins map to one account here.
 */
class SuperAppId
{
    public const PATTERN = '/^[A-Za-z0-9_-]{1,64}$/';

    /** Validation rules for the admin forms; $table is employees or students. */
    public static function rules(string $table, ?int $ignoreId = null): array
    {
        return ['superapp_id' => [
            'nullable', 'string', 'max:64', 'regex:' . self::PATTERN,
            'unique:' . $table . ',superapp_id' . ($ignoreId ? ',' . $ignoreId : ''),
        ]];
    }

    public static function messages(): array
    {
        return [
            'superapp_id.regex' => 'Super App ID hanya boleh berisi huruf, angka, "-" dan "_".',
            'superapp_id.unique' => 'Super App ID ini sudah dipakai akun lain.',
        ];
    }

    /**
     * Attribute to save on update: only when the form has the field, so other
     * forms that post to the same route never unlink an account.
     */
    public static function fromForm(Request $request): array
    {
        return $request->has('superapp_id') ? ['superapp_id' => self::fromRequest($request)] : [];
    }

    /** Trimmed value from the form; empty means "not linked". */
    public static function fromRequest(Request $request): ?string
    {
        $value = trim((string) $request->input('superapp_id', ''));

        return $value === '' ? null : $value;
    }
}
