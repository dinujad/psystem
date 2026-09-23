<?php

namespace App\Support;

use App\BusinessLocation;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Accountant2Permissions
{
    public const ROLE_BASE_NAME = 'ACCOUNTANT 2';

    /**
     * Full access for Accounting module + invoices, quotations, expenses, purchases, accounts.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            'access_all_locations',
            'dashboard.data',
            'view_export_buttons',

            'customer.view',
            'customer.create',
            'customer.update',
            'customer.delete',
            'supplier.view',
            'supplier.create',
            'supplier.update',
            'supplier.delete',

            'product.view',
            'view_purchase_price',
            'access_default_selling_price',

            'purchase.view',
            'purchase.create',
            'purchase.update',
            'purchase.delete',
            'purchase.payments',
            'edit_purchase_payment',
            'delete_purchase_payment',
            'purchase.update_status',
            'purchase_order.view_all',
            'purchase_order.create',
            'purchase_order.update',
            'purchase_order.delete',

            'sell.view',
            'sell.create',
            'sell.update',
            'sell.delete',
            'sell.payments',
            'edit_sell_payment',
            'delete_sell_payment',
            'print_invoice',
            'edit_product_price_from_sale_screen',
            'edit_product_discount_from_sale_screen',
            'edit_product_price_from_pos_screen',
            'edit_product_discount_from_pos_screen',
            'edit_pos_payment',

            'direct_sell.view',
            'direct_sell.access',
            'direct_sell.update',
            'direct_sell.delete',
            'access_sell_return',
            'access_own_sell_return',

            'draft.view_all',
            'draft.update',
            'draft.delete',
            'quotation.view_all',
            'quotation.update',
            'quotation.delete',

            'all_expense.access',
            'expense.add',
            'expense.edit',
            'expense.delete',
            'expense_report.view',

            'account.access',
            'edit_account_transaction',
            'delete_account_transaction',

            'purchase_n_sell_report.view',
            'contacts_report.view',
            'profit_loss_report.view',
            'stock_report.view',
            'tax_report.view',
            'trending_product_report.view',
            'sales_representative.view',
            'brand.view',

            'accounting.manage_accounts',
            'accounting.chart_of_accounts.index',
            'accounting.chart_of_accounts.create',
            'accounting.chart_of_accounts.edit',
            'accounting.chart_of_accounts.destroy',
            'accounting.journal_entries.index',
            'accounting.journal_entries.create',
            'accounting.journal_entries.edit',
            'accounting.journal_entries.reverse',
            'accounting.map_transactions',
            'accounting.view_transfer',
            'accounting.add_transfer',
            'accounting.edit_transfer',
            'accounting.delete_transfer',
            'accounting.reports.balance_sheet',
            'accounting.reports.trial_balance',
            'accounting.reports.income_statement',
            'accounting.reports.ledger',
        ];
    }

    public static function ensurePermissionsExist(): void
    {
        foreach (self::names() as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }

    public static function syncRoleForBusiness(int $businessId): Role
    {
        self::ensurePermissionsExist();

        $role = Role::firstOrCreate(
            [
                'name' => self::ROLE_BASE_NAME.'#'.$businessId,
                'guard_name' => 'web',
            ],
            [
                'business_id' => $businessId,
                'is_default' => 0,
            ]
        );

        $permissions = self::names();
        foreach (BusinessLocation::where('business_id', $businessId)->pluck('id') as $locationId) {
            $locPerm = 'location.'.$locationId;
            Permission::firstOrCreate([
                'name' => $locPerm,
                'guard_name' => 'web',
            ]);
            $permissions[] = $locPerm;
        }

        $role->syncPermissions(array_values(array_unique($permissions)));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }
}
