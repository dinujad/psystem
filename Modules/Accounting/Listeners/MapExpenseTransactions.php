<?php

namespace Modules\Accounting\Listeners;

class MapExpenseTransactions
{
    public function handle($event)
    {
        $accountingUtil = new \Modules\Accounting\Utils\AccountingUtil();
        if (isset($event->isDeleted) && $event->isDeleted) {
            $accountingUtil->deleteMap($event->expense->id, null);

            return;
        }

        $userId = request()->session()->get('user.id') ?: $event->expense->created_by;
        $accountingUtil->mapTransaction($event->expense, 'expense', $userId);
    }
}
