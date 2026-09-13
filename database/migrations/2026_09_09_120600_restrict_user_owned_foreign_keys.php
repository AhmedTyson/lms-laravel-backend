<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ADR-011: users owning courses or permission grants cannot be hard-deleted —
// soft-delete is the only supported removal path once such rows exist. These
// FKs already defaulted to RESTRICT implicitly; this migration makes the
// policy explicit and enforceable on MySQL. SQLite cannot ALTER foreign keys,
// but its engine already enforces equivalent NO ACTION behavior, so the alter
// is skipped there by design (not by oversight).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->foreign('instructor_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('permission_grants', function (Blueprint $table) {
            $table->dropForeign(['granter_id']);
            $table->foreign('granter_id')->references('id')->on('users')->restrictOnDelete();
            $table->dropForeign(['grantee_id']);
            $table->foreign('grantee_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->foreign('instructor_id')->references('id')->on('users');
        });

        Schema::table('permission_grants', function (Blueprint $table) {
            $table->dropForeign(['granter_id']);
            $table->foreign('granter_id')->references('id')->on('users');
            $table->dropForeign(['grantee_id']);
            $table->foreign('grantee_id')->references('id')->on('users');
        });
    }
};
