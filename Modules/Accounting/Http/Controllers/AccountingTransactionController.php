<?php

namespace Modules\Accounting\Http\Controllers;

use Modules\Accounting\Services\FlashService;
use Modules\Accounting\Entities\Transaction;
use Modules\Accounting\Utils\ExpenseUtil;
use Modules\Accounting\Utils\PurchaseUtil;
use Modules\Accounting\Utils\SellUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\ChartOfAccount;

class AccountingTransactionController extends Controller
{
    public function __construct(SellUtil $sellUtil, ExpenseUtil $expenseUtil, PurchaseUtil $purchaseUtil)
    {
        $this->sellUtil = $sellUtil;
        $this->expenseUtil = $expenseUtil;
        $this->purchaseUtil = $purchaseUtil;
    }

    public function sales()
    {
        switch (request()->type) {
            case 'payment':
                if (request()->ajax()) {
                    return $this->sellUtil->getTransactionsDataTable();
                }
                $data_array = $this->sellUtil->getData();
                $data_array = $this->mappingViewData($data_array, 'payment', 'credit', 'Sales');
                return view('accounting::transactions.sales.payment')->with($data_array);

            case 'invoice':
                if (request()->ajax()) {
                    return $this->sellUtil->getTransactionsDataTable();
                }
                $data_array = $this->sellUtil->getData();
                $data_array = $this->mappingViewData($data_array, 'invoice', 'credit', 'Sales');
                return view('accounting::transactions.sales.invoice')->with($data_array);

            default:
                abort(404);
                break;
        }
    }

    public function expenses()
    {
        if (!auth()->user()->can('all_expense.access') && !auth()->user()->can('view_own_expense')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            return $this->expenseUtil->getTransactionsDataTable();
        }

        $data_array = $this->expenseUtil->getData();
        $data_array = $this->mappingViewData($data_array, 'expense', 'debit', 'Expenses');

        return view('accounting::transactions.expenses')->with($data_array);
    }

    public function purchases()
    {
        switch (request()->type) {
            case 'purchase_order':
                if (!auth()->user()->can('purchase_order.view_all') && !auth()->user()->can('purchase_order.view_own')) {
                    abort(403, 'Unauthorized action.');
                }

                if (request()->ajax()) {
                    return $this->purchaseUtil->getPurchaseOrderTransactionsDataTable();
                }

                $data_array = $this->purchaseUtil->getPurchaseOrdersData();
                $data_array = $this->mappingViewData($data_array, 'purchase_order', 'debit', 'Purchase orders');
                return view('accounting::transactions.purchases.purchase_order')->with($data_array);

            case 'purchase_payment':
                if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create') && !auth()->user()->can('view_own_purchase')) {
                    abort(403, 'Unauthorized action.');
                }
                if (request()->ajax()) {
                    return $this->purchaseUtil->getPurchaseTransactionsDataTable();
                }
                $data_array = $this->purchaseUtil->getPurchaseData();
                $data_array = $this->mappingViewData($data_array, 'purchase_payment', 'debit', 'Purchases');
                return view('accounting::transactions.purchases.purchase_payment')->with($data_array);

            default:
                abort(404);
                break;
        }
    }

    public function map_to_chart_of_account(Request $request)
    {
        $request->validate([
            'map_type' => ['required'],
            'mapping_for' => ['required'],
            'chart_of_account_id' => ['required'],
            'transaction_id' => ['required'],
            'notes' => ['nullable'],
        ]);

        $mapper = new \Modules\Accounting\Utils\TransactionAccountMap();
        $category = $mapper->categoryFromMapping($request->mapping_for);
        if (empty($category)) {
            $category = $request->mapping_for;
        }

        try {
            DB::beginTransaction();

            $transaction = Transaction::where('id', $request->transaction_id)->firstOrFail();
            $mapper->remember((int) $transaction->business_id, $category, $request->map_type, (int) $request->chart_of_account_id);
            $mapper->mapOne($transaction, (int) $request->chart_of_account_id, $request->map_type, $request->notes, $category);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return (new  FlashService())->onException($e)->redirectBackWithInput();
        }

        $extra = $mapper->mapUnmapped(
            (int) $transaction->business_id,
            $category,
            $request->map_type,
            (int) $request->chart_of_account_id,
            $request->notes
        );

        $label = [
            'sale' => 'sale',
            'expense' => 'expense',
            'purchase' => 'purchase',
            'purchase_order' => 'purchase order',
        ][$category] ?? $category;

        $message = 'Saved. Every new '.$label.' will use this account.';
        if ($extra > 0) {
            $message .= ' '.$extra.' existing '.$label.($extra === 1 ? ' was' : 's were').' mapped too.';
        }
        (new FlashService())->onSuccess($message);
        request()->session()->flash('status.success', 1);
        request()->session()->flash('status.msg', $message);

        return back();
    }

    public function map_category(Request $request)
    {
        $request->validate([
            'map_type' => ['required'],
            'mapping_for' => ['required'],
            'chart_of_account_id' => ['required'],
        ]);

        $mapper = new \Modules\Accounting\Utils\TransactionAccountMap();
        $category = $mapper->categoryFromMapping($request->mapping_for);
        if (empty($category)) {
            $category = $request->mapping_for;
        }

        $businessId = (int) ($request->session()->get('user.business_id') ?: session('business.id'));
        $mapper->remember($businessId, $category, $request->map_type, (int) $request->chart_of_account_id);
        $count = $mapper->mapUnmapped(
            $businessId,
            $category,
            $request->map_type,
            (int) $request->chart_of_account_id,
            null
        );

        $label = [
            'sale' => 'sale',
            'expense' => 'expense',
            'purchase' => 'purchase',
            'purchase_order' => 'purchase order',
        ][$category] ?? $category;

        $message = 'Every '.$label.' now uses this account and is added to Journal Entries.';
        if ($count > 0) {
            $message .= ' '.$count.' existing '.$label.($count === 1 ? ' was' : 's were').' mapped.';
        }
        (new FlashService())->onSuccess($message);
        request()->session()->flash('status.success', 1);
        request()->session()->flash('status.msg', $message);

        return back();
    }

    private function mappingViewData(array $data, string $mappingFor, string $mapType, string $label): array
    {
        $mapper = new \Modules\Accounting\Utils\TransactionAccountMap();
        $category = $mapper->categoryFromMapping($mappingFor);
        $businessId = (int) (request()->session()->get('user.business_id') ?: session('business.id'));
        $linked = $category ? $mapper->linkedAccount($businessId, $category) : ['id' => null, 'name' => null];

        $data['chart_of_accounts'] = ChartOfAccount::where('active', 1)->orderBy('gl_code')->get();
        $data['mapping_for'] = $mappingFor;
        $data['map_type'] = $mapType;
        $data['map_label'] = $label;
        $data['linked_account_id'] = $linked['id'];
        $data['linked_account_name'] = $linked['name'];

        return $data;
    }
}
