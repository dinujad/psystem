<?php

namespace Modules\Accounting\Listeners;

use App\Events\PurchaseCreatedOrModified;

class MapPurchaseTransaction
{
    public function handle(PurchaseCreatedOrModified $event)
    {
        $accountingUtil = new \Modules\Accounting\Utils\AccountingUtil();
        if (isset($event->isDeleted) && $event->isDeleted) {
            $accountingUtil->deleteMap($event->transaction->id, null);

            return;
        }

        $userId = request()->session()->get('user.id') ?: $event->transaction->created_by;
        $accountingUtil->mapTransaction($event->transaction, 'purchase', $userId);
    }
}
