<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url', 2048);
            $table->string('method', 8)->default('GET');
            $table->unsignedSmallInteger('expected_status')->nullable();
            $table->string('keyword')->nullable();
            $table->unsignedSmallInteger('interval_minutes')->default(5);
            $table->unsignedTinyInteger('timeout_seconds')->default(10);
            $table->boolean('is_active')->default(true);

            $table->string('status', 16)->default('pending')->index();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->unsignedInteger('last_response_time_ms')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();
        });

        Schema::create('monitor_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_up');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('checked_at');

            $table->index(['monitor_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_checks');
        Schema::dropIfExists('monitors');
    }
};
