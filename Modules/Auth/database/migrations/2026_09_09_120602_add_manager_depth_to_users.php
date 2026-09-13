<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ADR-013: denormalized tree depth (root Admin = 0). A CACHE, not source of
// truth — manager_id traversal stays authoritative for cycle prevention
// (ADR-007). Maintained on write by the reassignment service, recomputing
// the whole subtree in the same transaction (Phase 5 wiring). No max-depth
// cap adopted. Existing rows backfill to 0 and converge on first write;
// Phase 5 seeds correct depths for fixtures.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('manager_depth')->default(0)->after('manager_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('manager_depth');
        });
    }
};
