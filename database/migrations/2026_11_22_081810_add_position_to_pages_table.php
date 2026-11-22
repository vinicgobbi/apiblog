<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Posição da página no menu do blog (1 = primeira). As páginas já existentes
     * recebem 1, 2, 3… na ordem de criação, que era a ordem usada no menu.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('icon');
        });

        DB::table('pages')
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->each(fn (int $id, int $index) => DB::table('pages')->where('id', $id)->update(['position' => $index + 1]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
