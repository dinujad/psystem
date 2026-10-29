<?php

namespace Modules\Accounting\Listeners;

use App\Events\SellCreatedOrModified;

class MapSellTransaction
{
    public function handle(SellCreatedOrModified $event)
    {
        $accountingUtil = new \Modules\Accounting\Utils\AccountingUtil();
        if (isset($event->isDeleted) && $event->isDeleted) {
            $accountingUtil->deleteMap($event->transaction->id, null);

            return;
        }

        (new \Modules\Accounting\Utils\TransactionAccountMap())->mapNew($event->transaction);

        $userId = request()->session()->get('user.id') ?: $event->transaction->created_by;
        $accountingUtil->mapTransaction($event->transaction, 'sell', $userId);
    }
}
