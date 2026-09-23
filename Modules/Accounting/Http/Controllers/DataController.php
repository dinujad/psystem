<?php

namespace Modules\Accounting\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{

    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function user_permissions()
    {
        return [
            ['value' => 'accounting.manage_accounts', 'label' => 'Manage Chart of Accounts / Ledger'],
            ['value' => 'accounting.chart_of_accounts.index', 'label' => 'View Chart of accounts'],
            ['value' => 'accounting.chart_of_accounts.create', 'label' => 'Create Chart of accounts'],
            ['value' => 'accounting.chart_of_accounts.edit', 'label' => 'Edit Chart of accounts'],
            ['value' => 'accounting.chart_of_accounts.destroy', 'label' => 'Delete Chart of accounts'],
            ['value' => 'accounting.journal_entries.index', 'label' => 'View Journal Entries'],
            ['value' => 'accounting.journal_entries.create', 'label' => 'Create Journal Entries'],
            ['value' => 'accounting.journal_entries.edit', 'label' => 'Edit Journal Entries'],
            ['value' => 'accounting.journal_entries.reverse', 'label' => 'Reverse Journal Entries'],
            ['value' => 'accounting.map_transactions', 'label' => 'Map Sales / Purchase / Expense Transactions'],
            ['value' => 'accounting.view_transfer', 'label' => 'View Transfers'],
            ['value' => 'accounting.add_transfer', 'label' => 'Add Transfers'],
            ['value' => 'accounting.edit_transfer', 'label' => 'Edit Transfers'],
            ['value' => 'accounting.delete_transfer', 'label' => 'Delete Transfers'],
            ['value' => 'accounting.reports.balance_sheet', 'label' => 'View Balance Sheet'],
            ['value' => 'accounting.reports.trial_balance', 'label' => 'View Trial Balance'],
            ['value' => 'accounting.reports.income_statement', 'label' => 'View Income Statement'],
            ['value' => 'accounting.reports.ledger', 'label' => 'View Ledger'],
        ];
    }

    public function superadmin_package()
    {
        return [
            [
                'name' => 'accounting_module',
                'label' => __('accounting::lang.accounting'),
                'default' => false
            ]
        ];
    }

    /**
     * Adds Accounting menus
     * @return null
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();
        $module_names = get_module_names();
        $is_accounting_enabled = (bool)$module_util->hasThePermissionInSubscription($business_id, $module_names->accounting);

        if ($is_accounting_enabled) {
            Menu::modify('admin-sidebar-menu', function ($menu) {
                $menu->url(
                    action('\Modules\Accounting\Http\Controllers\DashboardController@index'),
                    __('accounting::lang.accounting'),
                    [
                        'icon' => 'fa fas fa-book', 'id' => 'tour_step14',
                        'active' => request()->segment(1) == 'accounting' || request()->segment(2) == 'accounting'
                    ]
                )
                    ->order(24);
            });
        }
    }
}
