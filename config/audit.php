<?php

// Audit-trail policy values (ADR-014). Env-sourced here so commands can use
// config() safely under config:cache — never call env() outside config/.
return [
    'retention_days' => (int) env('ACTIVITY_LOG_RETENTION_DAYS', 90),
];
