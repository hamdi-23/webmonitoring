<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->unsignedTinyInteger('failure_threshold')->default(2)->after('timeout_seconds');
            $table->unsignedInteger('consecutive_failures')->default(0)->after('status_changed_at');
            $table->timestamp('first_failure_at')->nullable()->after('consecutive_failures');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['failure_threshold', 'consecutive_failures', 'first_failure_at']);
        });
    }
};
