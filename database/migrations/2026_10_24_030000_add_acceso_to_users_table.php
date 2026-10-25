<?php

use App\Models\User;
use App\Services\TotpService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_temporal')->default(false)->after('password');
            $table->string('totp_secret', 64)->nullable()->after('password_temporal');
            $table->boolean('totp_confirmado')->default(false)->after('totp_secret');
        });

        $totp = app(TotpService::class);

        User::query()->each(function (User $user) use ($totp) {
            $user->forceFill([
                'password_temporal' => true,
                'totp_secret' => $totp->secreto(),
                'totp_confirmado' => false,
            ])->save();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_temporal', 'totp_secret', 'totp_confirmado']);
        });
    }
};
