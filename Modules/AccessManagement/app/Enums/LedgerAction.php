<?php

namespace Modules\AccessManagement\Enums;

enum LedgerAction: string
{
    case Grant = 'grant';
    case Revoke = 'revoke';

    public function ledgerValue(): string
    {
        return match ($this) {
            self::Grant => 'granted',
            self::Revoke => 'revoked',
        };
    }
}
