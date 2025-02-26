<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('guard_name')->default('web')->after('name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->string('guard_name')->default('web')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('guard_name');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('guard_name');
        });
    }
};
