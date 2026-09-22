<?php

namespace Modules\Reporting\Database\Seeders;

use Illuminate\Database\Seeder;

class ReportingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reporting reads aggregates only — no owned tables, nothing to seed.
    }
}
