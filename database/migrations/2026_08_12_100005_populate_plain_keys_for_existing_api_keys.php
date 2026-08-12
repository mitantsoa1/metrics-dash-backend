<?php

declare(strict_types=1);

use App\Domains\Instance\Infrastructure\Models\ApiKeyModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $keys = ApiKeyModel::whereNull('plain_key')->orWhere('plain_key', '')->get();

        foreach ($keys as $key) {
            $prefix = ! empty($key->prefix) ? $key->prefix : ($key->type === 'PUBLIC_SDK' ? 'pk_live_' : 'sk_live_');
            $randomEntropy = Str::random(32);
            $newPlainKey = $prefix . $randomEntropy;

            $key->update([
                'prefix' => $prefix,
                'plain_key' => $newPlainKey,
                'key_hash' => hash('sha256', $newPlainKey),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
