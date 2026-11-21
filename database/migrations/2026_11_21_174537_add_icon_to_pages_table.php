<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ícone (nome do Bootstrap Icons, sem o prefixo "bi-") exibido no menu do blog.
     * As páginas já existentes recebem um ícone padrão; "sobre" ganha o de pessoa,
     * que era o usado no link fixo do menu. A obrigatoriedade fica no PageRequest.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('icon', 64)->default('file-earmark-text')->after('slug');
        });

        DB::table('pages')->where('slug', 'sobre')->update(['icon' => 'person']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
