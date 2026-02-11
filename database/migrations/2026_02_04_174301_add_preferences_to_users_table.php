<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->default('UTC')->after('email');
            $table->json('ui_preferences')->nullable()->after('timezone');
            $table->json('notification_preferences')->nullable()->after('ui_preferences');
            $table->text('billing_address')->nullable()->after('notification_preferences');
            $table->string('vat_number')->nullable()->after('billing_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'ui_preferences',
                'notification_preferences',
                'billing_address',
                'vat_number'
            ]);
        });
    }
};
