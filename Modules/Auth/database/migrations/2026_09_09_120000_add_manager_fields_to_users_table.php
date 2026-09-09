<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spec §5.2: manager tree link + instructor approval flag. Both nullable:
// students never get a manager; instructors get one at approval (RULE-010).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('manager_id')->nullable()->after('password')
                ->constrained('users')->nullOnDelete();
            $table->string('approval_status', 20)->nullable()->after('manager_id');
            $table->index('manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
            $table->dropColumn('approval_status');
        });
    }
};
