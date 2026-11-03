
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add additional account management fields to users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');

            $table->boolean('is_active')->default(true)->after('password');

            $table->timestamp('last_login_at')->nullable()->after('is_active');

            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');

            $table->boolean('force_password_change')
                ->default(false)
                ->after('last_login_ip');
        });
    }

    /**
     * Remove the additional account management fields.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'force_password_change',
            ]);
        });
    }
};
