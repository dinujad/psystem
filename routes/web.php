<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountReportsController;
use App\Http\Controllers\AccountTypeController;
// use App\Http\Controllers\Auth;
use App\Http\Controllers\BackUpController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessLocationController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CombinedPurchaseReturnController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\DashboardConfiguratorController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\DocumentAndNoteController;
use App\Http\Controllers\EmwaApiAdminController;
use App\Http\Controllers\EmwaApiController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupTaxController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportOpeningStockController;
use App\Http\Controllers\ImportProductsController;
use App\Http\Controllers\ImportSalesController;
use App\Http\Controllers\Install;
use App\Http\Controllers\InvoiceLayoutController;
use App\Http\Controllers\InvoiceSchemeController;
use App\Http\Controllers\LabelsController;
use App\Http\Controllers\LedgerDiscountController;
use App\Http\Controllers\LocationSettingsController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Restaurant;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesCommissionAgentController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SellingPriceGroupController;
use App\Http\Controllers\SellPosController;
use App\Http\Controllers\SellReturnController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SmsCampaignController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TransactionPaymentController;
use App\Http\Controllers\TypesOfServiceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationTemplateController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

include_once 'install_r.php';

Route::middleware(['setData'])->group(function () {
    Route::get('/', [\App\Http\Controllers\FrontendController::class, 'home'])->name('frontend.home');
    Route::get('/pricing', [\App\Http\Controllers\FrontendController::class, 'pricing'])->name('frontend.pricing');
    Route::get('/about', [\App\Http\Controllers\FrontendController::class, 'about'])->name('frontend.about');
    Route::get('/contact', [\App\Http\Controllers\FrontendController::class, 'contact'])->name('frontend.contact');

    Auth::routes();

    Route::get('/business/register', [BusinessController::class, 'getRegister'])->name('business.getRegister');
    Route::post('/business/register', [BusinessController::class, 'postRegister'])->name('business.postRegister');
    Route::post('/business/register/check-username', [BusinessController::class, 'postCheckUsername'])->name('business.postCheckUsername');
    Route::post('/business/register/check-email', [BusinessController::class, 'postCheckEmail'])->name('business.postCheckEmail');

    Route::get('/invoice/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_invoice');
    Route::get('/quote/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_quote');

    Route::get('/pay/{token}', [SellPosController::class, 'invoicePayment'])
        ->name('invoice_payment');
    Route::post('/confirm-payment/{id}', [SellPosController::class, 'confirmPayment'])
        ->name('confirm_payment');
});

Route::post('/whatsapp/webhook/incoming', [WhatsappController::class, 'incomingWebhook']);
Route::post('/whatsapp/webhook/contacts', [WhatsappController::class, 'contactWebhook']);
Route::post('/whatsapp/webhook/lid-merge', [WhatsappController::class, 'lidMergeWebhook']);
Route::post('/whatsapp/webhook/connected', [WhatsappController::class, 'connectedWebhook']);
Route::match(['get', 'post'], '/whatsapp/wipe-inbox', [WhatsappController::class, 'wipeInboxUrl']);

// Fardar Express reverse API (status callbacks)
Route::post('/delivery/webhook/status', [\App\Http\Controllers\DeliveryController::class, 'statusWebhook']);
// Public customer Tracking Portal (WhatsApp live-track links)
Route::get('/tracking-portal', [\App\Http\Controllers\DeliveryController::class, 'trackingPortalHome'])
    ->name('delivery.tracking_portal');
Route::get('/tracking-portal/{token}', [\App\Http\Controllers\DeliveryController::class, 'trackingPortalShow'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('delivery.tracking_portal.show');
// Legacy alias
Route::get('/track/{token}', [\App\Http\Controllers\DeliveryController::class, 'track'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('delivery.track');

// E MEDIA WhatsApp API (public — HostGrap-compatible endpoints via linked WhatsApp)
Route::prefix('emwa-api')->group(function () {
    Route::get('/register', [EmwaApiAdminController::class, 'showRegister'])->name('emwa.register');
    Route::post('/register', [EmwaApiAdminController::class, 'register']);
    Route::post('/api/send-message.php', [EmwaApiController::class, 'sendMessage']);
    Route::post('/api/send-image.php', [EmwaApiController::class, 'sendImage']);
    Route::post('/api/send-link-preview.php', [EmwaApiController::class, 'sendLinkPreview']);
    Route::post('/api/send-voice.php', [EmwaApiController::class, 'sendVoice']);
    Route::post('/api/send-poll.php', [EmwaApiController::class, 'sendPoll']);
    Route::post('/api/status/send-text.php', [EmwaApiController::class, 'sendStatusText']);
    Route::post('/api/status/delete.php', [EmwaApiController::class, 'deleteStatus']);
});

//Routes for authenticated users only
Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin'])->group(function () {
    Route::get('pos/payment/{id}', [SellPosController::class, 'edit'])->name('edit-pos-payment');
    Route::get('service-staff-availability', [SellPosController::class, 'showServiceStaffAvailibility']);
    Route::get('pause-resume-service-staff-timer/{user_id}', [SellPosController::class, 'pauseResumeServiceStaffTimer']);
    Route::get('mark-as-available/{user_id}', [SellPosController::class, 'markAsAvailable']);

    Route::resource('purchase-requisition', PurchaseRequisitionController::class)->except(['edit', 'update']);
    Route::post('/get-requisition-products', [PurchaseRequisitionController::class, 'getRequisitionProducts'])->name('get-requisition-products');
    Route::get('get-purchase-requisitions/{location_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitions']);
    Route::get('get-purchase-requisition-lines/{purchase_requisition_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitionLines']);

    Route::get('/sign-in-as-user/{id}', [ManageUserController::class, 'signInAsUser'])->name('sign-in-as-user');

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/get-totals', [HomeController::class, 'getTotals']);
    Route::get('/home/product-stock-alert', [HomeController::class, 'getProductStockAlert']);
    Route::get('/home/purchase-payment-dues', [HomeController::class, 'getPurchasePaymentDues']);
    Route::get('/home/sales-payment-dues', [HomeController::class, 'getSalesPaymentDues']);
    Route::post('/attach-medias-to-model', [HomeController::class, 'attachMediasToGivenModel'])->name('attach.medias.to.model');
    Route::get('/calendar', [HomeController::class, 'getCalendar'])->name('calendar');

    Route::post('/test-email', [BusinessController::class, 'testEmailConfiguration']);
    Route::post('/test-sms', [BusinessController::class, 'testSmsConfiguration']);
    Route::get('/business/settings', [BusinessController::class, 'getBusinessSettings'])->name('business.getBusinessSettings');
    Route::post('/business/update', [BusinessController::class, 'postBusinessSettings'])->name('business.postBusinessSettings');
    Route::get('/user/profile', [UserController::class, 'getProfile'])->name('user.getProfile');
    Route::post('/user/update', [UserController::class, 'updateProfile'])->name('user.updateProfile');
    Route::post('/user/update-password', [UserController::class, 'updatePassword'])->name('user.updatePassword');

    Route::resource('brands', BrandController::class);

    // Route::resource('payment-account', 'PaymentAccountController'); // Controller removed

    Route::resource('tax-rates', TaxRateController::class);

    Route::resource('units', UnitController::class);

    Route::resource('ledger-discount', LedgerDiscountController::class)->only('edit', 'destroy', 'store', 'update');

    Route::post('check-mobile', [ContactController::class, 'checkMobile']);
    Route::get('/get-contact-due/{contact_id}', [ContactController::class, 'getContactDue']);
    Route::get('/contacts/payments/{contact_id}', [ContactController::class, 'getContactPayments']);
    Route::get('/contacts/map', [ContactController::class, 'contactMap']);
    Route::get('/contacts/update-status/{id}', [ContactController::class, 'updateStatus']);
    Route::get('/contacts/stock-report/{supplier_id}', [ContactController::class, 'getSupplierStockReport']);
    Route::get('/contacts/ledger', [ContactController::class, 'getLedger']);
    Route::post('/contacts/send-ledger', [ContactController::class, 'sendLedger']);
    Route::get('/contacts/import', [ContactController::class, 'getImportContacts'])->name('contacts.import');
    Route::post('/contacts/import', [ContactController::class, 'postImportContacts']);
    Route::post('/contacts/check-contacts-id', [ContactController::class, 'checkContactId']);

    Route::post('/contacts/check-tax-number', [ContactController::class, 'checkTaxNumber']);

    Route::get('/contacts/customers', [ContactController::class, 'getCustomers']);
    Route::resource('contacts', ContactController::class);

    Route::get('taxonomies-ajax-index-page', [TaxonomyController::class, 'getTaxonomyIndexPage']);
    Route::resource('taxonomies', TaxonomyController::class);

    Route::resource('variation-templates', VariationTemplateController::class);

    Route::get('/products/download-excel', [ProductController::class, 'downloadExcel']);

    Route::get('/products/stock-history/{id}', [ProductController::class, 'productStockHistory']);
    Route::get('/delete-media/{media_id}', [ProductController::class, 'deleteMedia']);
    Route::post('/products/mass-deactivate', [ProductController::class, 'massDeactivate']);
    Route::get('/products/activate/{id}', [ProductController::class, 'activate']);
    Route::get('/products/view-product-group-price/{id}', [ProductController::class, 'viewGroupPrice']);
    Route::get('/products/add-selling-prices/{id}', [ProductController::class, 'addSellingPrices']);
    Route::post('/products/save-selling-prices', [ProductController::class, 'saveSellingPrices']);
    Route::post('/products/mass-delete', [ProductController::class, 'massDestroy']);
    Route::get('/products/view/{id}', [ProductController::class, 'view']);
    Route::get('/products/list', [ProductController::class, 'getProducts']);
    Route::get('/products/list-no-variation', [ProductController::class, 'getProductsWithoutVariations']);
    Route::post('/products/bulk-edit', [ProductController::class, 'bulkEdit']);
    Route::post('/products/bulk-update', [ProductController::class, 'bulkUpdate']);
    Route::post('/products/bulk-update-location', [ProductController::class, 'updateProductLocation']);
    Route::get('/products/get-product-to-edit/{product_id}', [ProductController::class, 'getProductToEdit']);

    Route::post('/products/get_sub_categories', [ProductController::class, 'getSubCategories']);
    Route::get('/products/get_sub_units', [ProductController::class, 'getSubUnits']);
    Route::post('/products/product_form_part', [ProductController::class, 'getProductVariationFormPart']);
    Route::post('/products/get_product_variation_row', [ProductController::class, 'getProductVariationRow']);
    Route::post('/products/get_variation_template', [ProductController::class, 'getVariationTemplate']);
    Route::get('/products/get_variation_value_row', [ProductController::class, 'getVariationValueRow']);
    Route::post('/products/check_product_sku', [ProductController::class, 'checkProductSku']);
    Route::post('/products/check_product_name', [ProductController::class, 'checkProductName']);
    Route::post('/products/validate_variation_skus', [ProductController::class, 'validateVaritionSkus']); //validates multiple skus at once
    Route::get('/products/quick_add', [ProductController::class, 'quickAdd']);
    Route::post('/products/save_quick_product', [ProductController::class, 'saveQuickProduct']);
    Route::get('/products/get-combo-product-entry-row', [ProductController::class, 'getComboProductEntryRow']);
    Route::post('/products/toggle-woocommerce-sync', [ProductController::class, 'toggleWooCommerceSync']);

    Route::resource('products', ProductController::class);
    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');
    Route::post('/sells/pos/get-types-of-service-details', 'SellPosController@getTypesOfServiceDetails');
    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/drafts', 'SellController@getDrafts');
    Route::get('/sells/convert-to-draft/{id}', 'SellPosController@convertToInvoice');
    Route::get('/sells/convert-to-proforma/{id}', 'SellPosController@convertToProforma');
    Route::get('/sells/quotations', 'SellController@getQuotations');
    Route::get('/sells/proformas', 'SellController@getProformas');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');
    Route::resource('sells', 'SellController')->except(['show']);
    Route::get('/sells/copy-quotation/{id}', [SellPosController::class, 'copyQuotation']);

    Route::post('/import-purchase-products', [PurchaseController::class, 'importPurchaseProducts']);
    Route::post('/purchases/update-status', [PurchaseController::class, 'updateStatus']);
    Route::get('/purchases/get_products', [PurchaseController::class, 'getProducts']);
    Route::get('/purchases/get_suppliers', [PurchaseController::class, 'getSuppliers']);
    Route::post('/purchases/get_purchase_entry_row', [PurchaseController::class, 'getPurchaseEntryRow']);
    Route::post('/purchases/check_ref_number', [PurchaseController::class, 'checkRefNumber']);
    Route::resource('purchases', PurchaseController::class)->except(['show']);

    Route::get('/toggle-subscription/{id}', [SellPosController::class, 'toggleRecurringInvoices']);
    Route::post('/sells/pos/get-types-of-service-details', [SellPosController::class, 'getTypesOfServiceDetails']);
    Route::get('/sells/subscriptions', [SellPosController::class, 'listSubscriptions']);
    Route::get('/sells/duplicate/{id}', [SellController::class, 'duplicateSell']);
    Route::get('/sells/drafts', [SellController::class, 'getDrafts']);
    Route::get('/sells/convert-to-draft/{id}', [SellPosController::class, 'convertToInvoice']);
    Route::get('/sells/convert-to-proforma/{id}', [SellPosController::class, 'convertToProforma']);
    Route::get('/sells/quotations', [SellController::class, 'getQuotations']);
    Route::get('/sells/proformas', [SellController::class, 'getProformas']);
    Route::get('/sells/draft-dt', [SellController::class, 'getDraftDatables']);
    Route::resource('sells', SellController::class)->except(['show']);

    Route::get('/import-sales', [ImportSalesController::class, 'index']);
    Route::post('/import-sales/preview', [ImportSalesController::class, 'preview']);
    Route::post('/import-sales', [ImportSalesController::class, 'import']);
    Route::get('/revert-sale-import/{batch}', [ImportSalesController::class, 'revertSaleImport']);

    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', [SellPosController::class, 'getProductRow']);
    Route::post('/sells/pos/get_payment_row', [SellPosController::class, 'getPaymentRow']);
    Route::post('/sells/pos/get-reward-details', [SellPosController::class, 'getRewardDetails']);
    Route::get('/sells/pos/get-recent-transactions', [SellPosController::class, 'getRecentTransactions']);
    Route::get('/sells/pos/get-product-suggestion', [SellPosController::class, 'getProductSuggestion']);
    Route::get('/sells/pos/get-featured-products/{location_id}', [SellPosController::class, 'getFeaturedProducts']);
    Route::get('/reset-mapping', [SellController::class, 'resetMapping']);
    // pos display screen route
    Route::get('/customer-display', [SellPosController::class, 'posDisplay'])->name('pos_display');

    Route::get('/pos/variations/bulk', [\App\Http\Controllers\ProductController::class, 'getVariationDetailsBulk']);
    // end pos display screen route
    Route::resource('pos', SellPosController::class);

    Route::resource('roles', RoleController::class);

    Route::resource('users', ManageUserController::class);

    Route::resource('group-taxes', GroupTaxController::class);

    Route::get('/barcodes/set_default/{id}', [BarcodeController::class, 'setDefault']);
    Route::resource('barcodes', BarcodeController::class);

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', [InvoiceSchemeController::class, 'setDefault']);
    Route::resource('invoice-schemes', InvoiceSchemeController::class);

    //Print Labels
    Route::get('/labels/show', [LabelsController::class, 'show']);
    Route::get('/labels/add-product-row', [LabelsController::class, 'addProductRow']);
    Route::get('/labels/preview', [LabelsController::class, 'preview']);

    //Reports...
    Route::get('/reports/gst-purchase-report', [ReportController::class, 'gstPurchaseReport']);
    Route::get('/reports/gst-sales-report', [ReportController::class, 'gstSalesReport']);
    Route::get('/reports/get-stock-by-sell-price', [ReportController::class, 'getStockBySellingPrice']);
    Route::get('/reports/purchase-report', [ReportController::class, 'purchaseReport']);
    Route::get('/reports/sale-report', [ReportController::class, 'saleReport']);
    Route::get('/reports/service-staff-report', [ReportController::class, 'getServiceStaffReport']);
    Route::get('/reports/service-staff-line-orders', [ReportController::class, 'serviceStaffLineOrders']);
    Route::get('/reports/table-report', [ReportController::class, 'getTableReport']);
    Route::get('/reports/profit-loss', [ReportController::class, 'getProfitLoss']);
    Route::get('/reports/production-costs', [\App\Http\Controllers\ProductionReportController::class, 'productionCosts'])->name('reports.production-costs');
    Route::get('/reports/production-report', [\App\Http\Controllers\ProductionReportController::class, 'productionReport'])->name('reports.production-report');
    Route::get('/reports/get-opening-stock', [ReportController::class, 'getOpeningStock']);
    Route::get('/reports/purchase-sell', [ReportController::class, 'getPurchaseSell']);
    Route::get('/reports/customer-supplier', [ReportController::class, 'getCustomerSuppliers']);
    Route::get('/reports/stock-report', [ReportController::class, 'getStockReport']);
    Route::get('/reports/stock-details', [ReportController::class, 'getStockDetails']);
    Route::get('/reports/tax-report', [ReportController::class, 'getTaxReport']);
    Route::get('/reports/tax-details', [ReportController::class, 'getTaxDetails']);
    Route::get('/reports/trending-products', [ReportController::class, 'getTrendingProducts']);
    Route::get('/reports/expense-report', [ReportController::class, 'getExpenseReport']);
    Route::get('/reports/stock-adjustment-report', [ReportController::class, 'getStockAdjustmentReport']);
    Route::get('/reports/register-report', [ReportController::class, 'getRegisterReport']);
    Route::get('/reports/sales-representative-report', [ReportController::class, 'getSalesRepresentativeReport']);
    Route::get('/reports/sales-representative-total-expense', [ReportController::class, 'getSalesRepresentativeTotalExpense']);
    Route::get('/reports/sales-representative-total-sell', [ReportController::class, 'getSalesRepresentativeTotalSell']);
    Route::get('/reports/sales-representative-total-commission', [ReportController::class, 'getSalesRepresentativeTotalCommission']);
    Route::get('/reports/stock-expiry', [ReportController::class, 'getStockExpiryReport']);
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', [ReportController::class, 'getStockExpiryReportEditModal']);
    Route::post('/reports/stock-expiry-update', [ReportController::class, 'updateStockExpiryReport'])->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', [ReportController::class, 'getCustomerGroup']);
    Route::get('/reports/product-purchase-report', [ReportController::class, 'getproductPurchaseReport']);
    Route::get('/reports/product-sell-grouped-by', [ReportController::class, 'productSellReportBy']);
    Route::get('/reports/product-sell-report', [ReportController::class, 'getproductSellReport']);
    Route::get('/reports/product-sell-report-with-purchase', [ReportController::class, 'getproductSellReportWithPurchase']);
    Route::get('/reports/product-sell-grouped-report', [ReportController::class, 'getproductSellGroupedReport']);
    Route::get('/reports/lot-report', [ReportController::class, 'getLotReport']);
    Route::get('/reports/purchase-payment-report', [ReportController::class, 'purchasePaymentReport']);
    Route::get('/reports/sell-payment-report', [ReportController::class, 'sellPaymentReport']);
    Route::get('/reports/product-stock-details', [ReportController::class, 'productStockDetails']);
    Route::get('/reports/adjust-product-stock', [ReportController::class, 'adjustProductStock']);
    Route::get('/reports/get-profit/{by?}', [ReportController::class, 'getProfit']);
    Route::get('/reports/items-report', [ReportController::class, 'itemsReport']);
    Route::get('/reports/get-stock-value', [ReportController::class, 'getStockValue']);

    Route::get('business-location/activate-deactivate/{location_id}', [BusinessLocationController::class, 'activateDeactivateLocation']);

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', [LocationSettingsController::class, 'index'])->name('settings');
        Route::post('settings', [LocationSettingsController::class, 'updateSettings'])->name('settings_update');
    });

    //Business Locations...
    Route::post('business-location/check-location-id', [BusinessLocationController::class, 'checkLocationId']);
    Route::resource('business-location', BusinessLocationController::class);

    //Invoice layouts..
    Route::resource('invoice-layouts', InvoiceLayoutController::class);

    Route::post('get-expense-sub-categories', [ExpenseCategoryController::class, 'getSubCategories']);

    //Expense Categories...
    Route::resource('expense-categories', ExpenseCategoryController::class);

    //Expenses...
    Route::resource('expenses', ExpenseController::class);
    Route::get('import-expense', [ExpenseController::class, 'importExpense']);
    Route::post('store-import-expense', [ExpenseController::class, 'storeExpenseImport']);

    //Transaction payments...
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', [TransactionPaymentController::class, 'showChildPayments']);
    Route::get('/payments/view-payment/{payment_id}', [TransactionPaymentController::class, 'viewPayment']);
    Route::get('/payments/add_payment/{transaction_id}', [TransactionPaymentController::class, 'addPayment']);
    Route::get('/payments/pay-contact-due/{contact_id}', [TransactionPaymentController::class, 'getPayContactDue']);
    Route::post('/payments/pay-contact-due', [TransactionPaymentController::class, 'postPayContactDue']);
    Route::resource('payments', TransactionPaymentController::class);

    //Printers...
    Route::resource('printers', PrinterController::class);

    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', [StockAdjustmentController::class, 'removeExpiredStock']);
    Route::post('/stock-adjustments/get_product_row', [StockAdjustmentController::class, 'getProductRow']);
    Route::resource('stock-adjustments', StockAdjustmentController::class);

    Route::get('/cash-register/register-details', [CashRegisterController::class, 'getRegisterDetails']);
    Route::get('/cash-register/close-register/{id?}', [CashRegisterController::class, 'getCloseRegister']);
    Route::post('/cash-register/close-register', [CashRegisterController::class, 'postCloseRegister']);
    Route::resource('cash-register', CashRegisterController::class);

    //Import products
    Route::get('/import-products', [ImportProductsController::class, 'index']);
    Route::post('/import-products/store', [ImportProductsController::class, 'store']);

    //Sales Commission Agent
    Route::resource('sales-commission-agents', SalesCommissionAgentController::class);

    //Stock Transfer
    Route::get('stock-transfers/print/{id}', [StockTransferController::class, 'printInvoice']);
    Route::post('stock-transfers/update-status/{id}', [StockTransferController::class, 'updateStatus']);
    Route::resource('stock-transfers', StockTransferController::class);

    Route::get('/opening-stock/add/{product_id}', [OpeningStockController::class, 'add']);
    Route::post('/opening-stock/save', [OpeningStockController::class, 'save']);

    //Customer Groups
    Route::resource('customer-group', CustomerGroupController::class);

    //Import opening stock
    Route::get('/import-opening-stock', [ImportOpeningStockController::class, 'index']);
    Route::post('/import-opening-stock/store', [ImportOpeningStockController::class, 'store']);

    //Sell return
    Route::get('validate-invoice-to-return/{invoice_no}', [SellReturnController::class, 'validateInvoiceToReturn']);
    // service staff replacement
    Route::get('validate-invoice-to-service-staff-replacement/{invoice_no}', [SellPosController::class, 'validateInvoiceToServiceStaffReplacement']);
    Route::put('change-service-staff/{id}', [SellPosController::class, 'change_service_staff'])->name('change_service_staff');

    Route::resource('sell-return', SellReturnController::class);
    Route::get('sell-return/get-product-row', [SellReturnController::class, 'getProductRow']);
    Route::get('/sell-return/print/{id}', [SellReturnController::class, 'printInvoice']);
    Route::get('/sell-return/add/{id}', [SellReturnController::class, 'add']);

    //Backup
    Route::get('backup/download/{file_name}', [BackUpController::class, 'download']);
    Route::get('backup/{id}/delete', [BackUpController::class, 'delete'])->name('delete_backup');
    Route::resource('backup', BackUpController::class)->only('index', 'create', 'store');

    Route::get('selling-price-group/activate-deactivate/{id}', [SellingPriceGroupController::class, 'activateDeactivate']);
    Route::get('update-product-price', [SellingPriceGroupController::class, 'updateProductPrice'])->name('update-product-price');
    Route::get('export-product-price', [SellingPriceGroupController::class, 'export']);
    Route::post('import-product-price', [SellingPriceGroupController::class, 'import']);

    Route::resource('selling-price-group', SellingPriceGroupController::class);

    Route::resource('notification-templates', NotificationTemplateController::class)->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', [NotificationController::class, 'getTemplate']);
    Route::post('notification/send', [NotificationController::class, 'send']);
    Route::get('sms-campaigns', [SmsCampaignController::class, 'index'])->name('sms-campaigns.index');
    Route::post('sms-campaigns/send', [SmsCampaignController::class, 'send'])->name('sms-campaigns.send');

    Route::post('/purchase-return/update', [CombinedPurchaseReturnController::class, 'update']);
    Route::get('/purchase-return/edit/{id}', [CombinedPurchaseReturnController::class, 'edit']);
    Route::post('/purchase-return/save', [CombinedPurchaseReturnController::class, 'save']);
    Route::post('/purchase-return/get_product_row', [CombinedPurchaseReturnController::class, 'getProductRow']);
    Route::get('/purchase-return/create', [CombinedPurchaseReturnController::class, 'create']);
    Route::get('/purchase-return/add/{id}', [PurchaseReturnController::class, 'add']);
    Route::resource('/purchase-return', PurchaseReturnController::class)->except('create');

    Route::get('/discount/activate/{id}', [DiscountController::class, 'activate']);
    Route::post('/discount/mass-deactivate', [DiscountController::class, 'massDeactivate']);
    Route::resource('discount', DiscountController::class);

    Route::prefix('account')->group(function () {
        Route::resource('/account', AccountController::class);
        Route::get('/fund-transfer/{id}', [AccountController::class, 'getFundTransfer']);
        Route::post('/fund-transfer', [AccountController::class, 'postFundTransfer']);
        Route::get('/deposit/{id}', [AccountController::class, 'getDeposit']);
        Route::post('/deposit', [AccountController::class, 'postDeposit']);
        Route::get('/close/{id}', [AccountController::class, 'close']);
        Route::get('/activate/{id}', [AccountController::class, 'activate']);
        Route::get('/delete-account-transaction/{id}', [AccountController::class, 'destroyAccountTransaction']);
        Route::get('/edit-account-transaction/{id}', [AccountController::class, 'editAccountTransaction']);
        Route::post('/update-account-transaction/{id}', [AccountController::class, 'updateAccountTransaction']);
        Route::get('/get-account-balance/{id}', [AccountController::class, 'getAccountBalance']);
        Route::get('/balance-sheet', [AccountReportsController::class, 'balanceSheet']);
        Route::get('/trial-balance', [AccountReportsController::class, 'trialBalance']);
        Route::get('/payment-account-report', [AccountReportsController::class, 'paymentAccountReport']);
        Route::get('/link-account/{id}', [AccountReportsController::class, 'getLinkAccount']);
        Route::post('/link-account', [AccountReportsController::class, 'postLinkAccount']);
        Route::get('/cash-flow', [AccountController::class, 'cashFlow']);
    });

    Route::resource('account-types', AccountTypeController::class);

    //Restaurant module
    Route::prefix('modules')->group(function () {
        Route::resource('tables', Restaurant\TableController::class);
        Route::resource('modifiers', Restaurant\ModifierSetsController::class);

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', [Restaurant\ProductModifierSetController::class, 'edit']);
        Route::post('/product-modifiers/{id}/update', [Restaurant\ProductModifierSetController::class, 'update']);
        Route::get('/product-modifiers/product-row/{product_id}', [Restaurant\ProductModifierSetController::class, 'product_row']);

        Route::get('/add-selected-modifiers', [Restaurant\ProductModifierSetController::class, 'add_selected_modifiers']);

        Route::get('/kitchen', [Restaurant\KitchenController::class, 'index']);
        Route::get('/kitchen/mark-as-cooked/{id}', [Restaurant\KitchenController::class, 'markAsCooked']);
        Route::post('/refresh-orders-list', [Restaurant\KitchenController::class, 'refreshOrdersList']);
        Route::post('/refresh-line-orders-list', [Restaurant\KitchenController::class, 'refreshLineOrdersList']);

        Route::get('/orders', [Restaurant\OrderController::class, 'index']);
        Route::get('/orders/mark-as-served/{id}', [Restaurant\OrderController::class, 'markAsServed']);
        Route::get('/data/get-pos-details', [Restaurant\DataController::class, 'getPosDetails']);
        Route::get('/data/check-staff-pin', [Restaurant\DataController::class, 'checkStaffPin']);
        Route::get('/orders/mark-line-order-as-served/{id}', [Restaurant\OrderController::class, 'markLineOrderAsServed']);
        Route::get('/print-line-order', [Restaurant\OrderController::class, 'printLineOrder']);
    });

    Route::get('bookings/get-todays-bookings', [Restaurant\BookingController::class, 'getTodaysBookings']);
    Route::resource('bookings', Restaurant\BookingController::class);

    Route::resource('types-of-service', TypesOfServiceController::class);
    Route::get('sells/edit-shipping/{id}', [SellController::class, 'editShipping']);
    Route::put('sells/update-shipping/{id}', [SellController::class, 'updateShipping']);
    Route::get('shipments', [SellController::class, 'shipments']);

    Route::post('upload-module', [Install\ModulesController::class, 'uploadModule']);
    Route::delete('manage-modules/destroy/{module_name}', [Install\ModulesController::class, 'destroy']);
    Route::resource('manage-modules', Install\ModulesController::class)
        ->only(['index', 'update']);
    Route::get('regenerate', [Install\ModulesController::class, 'regenerate']);

    Route::resource('warranties', WarrantyController::class);

    Route::resource('dashboard-configurator', DashboardConfiguratorController::class)
        ->only(['edit', 'update']);

    Route::get('view-media/{model_id}', [SellController::class, 'viewMedia']);

    //common controller for document & note
    Route::get('get-document-note-page', [DocumentAndNoteController::class, 'getDocAndNoteIndexPage']);
    Route::post('post-document-upload', [DocumentAndNoteController::class, 'postMedia']);
    Route::resource('note-documents', DocumentAndNoteController::class);
    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::get('get-purchase-orders/{contact_id}', [PurchaseOrderController::class, 'getPurchaseOrders']);
    Route::get('get-purchase-order-lines/{purchase_order_id}', [PurchaseController::class, 'getPurchaseOrderLines']);
    Route::get('edit-purchase-orders/{id}/status', [PurchaseOrderController::class, 'getEditPurchaseOrderStatus']);
    Route::put('update-purchase-orders/{id}/status', [PurchaseOrderController::class, 'postEditPurchaseOrderStatus']);
    Route::resource('sales-order', SalesOrderController::class)->only(['index']);
    Route::get('get-sales-orders/{customer_id}', [SalesOrderController::class, 'getSalesOrders']);
    Route::get('get-sales-order-lines', [SellPosController::class, 'getSalesOrderLines']);
    Route::get('edit-sales-orders/{id}/status', [SalesOrderController::class, 'getEditSalesOrderStatus']);
    Route::put('update-sales-orders/{id}/status', [SalesOrderController::class, 'postEditSalesOrderStatus']);
    Route::get('reports/activity-log', [ReportController::class, 'activityLog']);

    Route::get('/whatsapp/link', [WhatsappController::class, 'showQr'])->name('whatsapp.link');
    Route::get('/whatsapp/qr', [WhatsappController::class, 'qr']);
    Route::get('/whatsapp/status', [WhatsappController::class, 'status']);
    Route::get('/whatsapp/sync-status', [WhatsappController::class, 'syncStatus']);
    Route::post('/whatsapp/sync-contacts', [WhatsappController::class, 'syncContacts']);
    Route::post('/whatsapp/send', [WhatsappController::class, 'send'])->middleware('throttle:whatsapp-send');
    Route::post('/whatsapp/logout', [WhatsappController::class, 'logout']);
    Route::post('/whatsapp/clear-inbox', [WhatsappController::class, 'clearInbox']);
    Route::get('/whatsapp/inbox', [WhatsappController::class, 'inbox'])->name('whatsapp.inbox');
    Route::get('/whatsapp/inbox/{phone}', [WhatsappController::class, 'conversation'])->name('whatsapp.conversation');
    Route::post('/whatsapp/inbox/send', [WhatsappController::class, 'sendFromInbox'])->middleware('throttle:whatsapp-send')->name('whatsapp.sendFromInbox');
    Route::get('/whatsapp/inbox/{phone}/poll', [WhatsappController::class, 'pollMessages']);
    Route::post('/whatsapp/inbox/{phone}/read', [WhatsappController::class, 'markRead'])->name('whatsapp.markRead');
    Route::get('/whatsapp/threads/poll', [WhatsappController::class, 'pollThreads'])->name('whatsapp.pollThreads');
    Route::get('/whatsapp/media/{path}', [WhatsappController::class, 'serveMedia'])->where('path', '.*')->name('whatsapp.media');
    Route::get('/whatsapp/avatar/{phone}', [WhatsappController::class, 'serveAvatar'])->name('whatsapp.avatar');
    Route::post('/whatsapp/fix-lid', [WhatsappController::class, 'fixLid'])->name('whatsapp.fixLid');

    // ── WhatsApp Bot automation engine (admin) ──────────────────────────
    Route::prefix('admin/whatsapp')->name('admin.whatsapp.')->group(function () {
        // Flows
        Route::get('flows', [\App\Http\Controllers\WhatsappFlowController::class, 'index'])->name('flows.index');
        Route::post('flows', [\App\Http\Controllers\WhatsappFlowController::class, 'store'])->name('flows.store');
        Route::put('flows/{flow}', [\App\Http\Controllers\WhatsappFlowController::class, 'update'])->name('flows.update');
        Route::delete('flows/{flow}', [\App\Http\Controllers\WhatsappFlowController::class, 'destroy'])->name('flows.destroy');
        Route::post('flows/{flow}/toggle', [\App\Http\Controllers\WhatsappFlowController::class, 'toggle'])->name('flows.toggle');

        // Flow builder (steps)
        Route::get('flows/{flow}/builder', [\App\Http\Controllers\WhatsappFlowController::class, 'builder'])->name('flows.builder');
        Route::post('flows/{flow}/steps', [\App\Http\Controllers\WhatsappFlowController::class, 'storeStep'])->name('steps.store');
        Route::put('steps/{step}', [\App\Http\Controllers\WhatsappFlowController::class, 'updateStep'])->name('steps.update');
        Route::delete('steps/{step}', [\App\Http\Controllers\WhatsappFlowController::class, 'destroyStep'])->name('steps.destroy');

        // Live conversations
        Route::get('conversations', [\App\Http\Controllers\WhatsappBotController::class, 'index'])->name('conversations.index');
        Route::get('conversations/poll', [\App\Http\Controllers\WhatsappBotController::class, 'poll'])->name('conversations.poll');
        Route::get('conversations/{conversation}', [\App\Http\Controllers\WhatsappBotController::class, 'show'])->name('conversations.show');
        Route::get('conversations/{conversation}/poll', [\App\Http\Controllers\WhatsappBotController::class, 'pollThread'])->name('conversations.pollThread');
        Route::post('conversations/{conversation}/reply', [\App\Http\Controllers\WhatsappBotController::class, 'reply'])->name('conversations.reply');
        Route::post('conversations/{conversation}/return-to-bot', [\App\Http\Controllers\WhatsappBotController::class, 'returnToBot'])->name('conversations.returnToBot');
    });

    // WhatsApp Agent assignment
    Route::prefix('admin/whatsapp/agents')->name('admin.whatsapp.agents.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WhatsappAgentController::class, 'agents'])->name('index');
        Route::get('/list', [\App\Http\Controllers\WhatsappAgentController::class, 'agentList'])->name('list');
        Route::get('/assignment/{phone}', [\App\Http\Controllers\WhatsappAgentController::class, 'assignmentFor'])->name('for');
        Route::post('/assign/{phone}', [\App\Http\Controllers\WhatsappAgentController::class, 'assign'])->name('assign');
        Route::post('/claim/{phone}', [\App\Http\Controllers\WhatsappAgentController::class, 'claim'])->name('claim');
        Route::post('/transfer/{phone}', [\App\Http\Controllers\WhatsappAgentController::class, 'transfer'])->name('transfer');
        Route::post('/close/{phone}', [\App\Http\Controllers\WhatsappAgentController::class, 'close'])->name('close');
        Route::post('/unassign/{phone}', [\App\Http\Controllers\WhatsappAgentController::class, 'unassign'])->name('unassign');
    });
    Route::get('/admin/whatsapp/reports', [\App\Http\Controllers\WhatsappAgentController::class, 'reports'])->name('admin.whatsapp.reports');
    Route::get('/admin/whatsapp/inquiries/{id}', [\App\Http\Controllers\WhatsappAgentController::class, 'inquiryShow'])->name('admin.whatsapp.inquiries.show');
    Route::post('/admin/whatsapp/inquiries/{id}/status', [\App\Http\Controllers\WhatsappAgentController::class, 'updateStatus'])->name('admin.whatsapp.inquiries.status');
    Route::get('/admin/whatsapp/inquiries/{id}/history', [\App\Http\Controllers\WhatsappAgentController::class, 'statusHistory'])->name('admin.whatsapp.inquiries.history');

    // WhatsApp Contacts (save name, get contact info, delete chat)
    Route::get('/whatsapp/contact/{phone}', [WhatsappController::class, 'getContact'])->name('whatsapp.contact.get');
    Route::post('/whatsapp/contact/{phone}', [WhatsappController::class, 'saveContact'])->name('whatsapp.contact.save');
    Route::delete('/whatsapp/chat/{phone}', [WhatsappController::class, 'deleteChat'])->name('whatsapp.chat.delete');

    // WhatsApp Labels
    Route::prefix('admin/whatsapp/labels')->name('admin.whatsapp.labels.')->group(function () {
        Route::get('/', [\App\Http\Controllers\WhatsappLabelController::class, 'index'])->name('index');
        Route::get('/all', [\App\Http\Controllers\WhatsappLabelController::class, 'all'])->name('all');
        Route::post('/', [\App\Http\Controllers\WhatsappLabelController::class, 'store'])->name('store');
        Route::put('/{label}', [\App\Http\Controllers\WhatsappLabelController::class, 'update'])->name('update');
        Route::delete('/{label}', [\App\Http\Controllers\WhatsappLabelController::class, 'destroy'])->name('destroy');
        Route::post('/{label}/assign', [\App\Http\Controllers\WhatsappLabelController::class, 'assign'])->name('assign');
        Route::post('/{label}/remove', [\App\Http\Controllers\WhatsappLabelController::class, 'remove'])->name('remove');
    });

    // E MEDIA WhatsApp API — admin management
    Route::prefix('admin/whatsapp/emwa-api')->name('admin.whatsapp.emwa.')->group(function () {
        Route::get('/', [EmwaApiAdminController::class, 'index'])->name('index');
        Route::post('/clients', [EmwaApiAdminController::class, 'store'])->name('store');
        Route::post('/clients/{id}/approve', [EmwaApiAdminController::class, 'approve'])->name('approve');
        Route::post('/clients/{id}/revoke', [EmwaApiAdminController::class, 'revoke'])->name('revoke');
        Route::post('/clients/{id}/regenerate', [EmwaApiAdminController::class, 'regenerateKey'])->name('regenerate');
        Route::delete('/clients/{id}', [EmwaApiAdminController::class, 'destroy'])->name('destroy');
    });

    Route::get('user-location/{latlng}', [HomeController::class, 'getUserLocation']);

    // ── Inventory Module ─────────────────────────────────────────────────────
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [\App\Http\Controllers\InventoryController::class, 'materials'])->name('index');
        Route::get('/search', [\App\Http\Controllers\InventoryController::class, 'searchMaterials'])->name('search');
        Route::post('/materials', [\App\Http\Controllers\InventoryController::class, 'storeMaterial'])->name('materials.store');
        Route::put('/materials/{material}', [\App\Http\Controllers\InventoryController::class, 'updateMaterial'])->name('materials.update');
        Route::delete('/materials/{material}', [\App\Http\Controllers\InventoryController::class, 'destroyMaterial'])->name('materials.destroy');
        Route::get('/categories', [\App\Http\Controllers\InventoryController::class, 'categories'])->name('categories');
        Route::post('/categories', [\App\Http\Controllers\InventoryController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\InventoryController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\InventoryController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('/units', [\App\Http\Controllers\InventoryController::class, 'units'])->name('units');
        Route::post('/units', [\App\Http\Controllers\InventoryController::class, 'storeUnit'])->name('units.store');
        Route::put('/units/{unit}', [\App\Http\Controllers\InventoryController::class, 'updateUnit'])->name('units.update');
        Route::delete('/units/{unit}', [\App\Http\Controllers\InventoryController::class, 'destroyUnit'])->name('units.destroy');
    });

    // ── Delivery (Fardar Express) ────────────────────────────────────────────
    Route::prefix('delivery')->name('delivery.')->group(function () {
        Route::get('/', [\App\Http\Controllers\DeliveryController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\DeliveryController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\DeliveryController::class, 'store'])->name('store');
        Route::get('/sales/search', [\App\Http\Controllers\DeliveryController::class, 'searchSales'])->name('sales.search');
        Route::get('/sale/{transactionId}/packing-slip', [\App\Http\Controllers\DeliveryController::class, 'packingSlipForSale'])
            ->name('sale_packing_slip')
            ->whereNumber('transactionId');
        Route::get('/{id}/packing-slip', [\App\Http\Controllers\DeliveryController::class, 'packingSlip'])
            ->name('packing_slip')
            ->whereNumber('id');
        Route::get('/{id}', [\App\Http\Controllers\DeliveryController::class, 'show'])->name('show')->whereNumber('id');
    });

    // ── Production Module ────────────────────────────────────────────────────
    Route::prefix('production')->name('production.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProductionController::class, 'index'])->name('index');
        Route::get('/jobs', [\App\Http\Controllers\ProductionController::class, 'allJobs'])->name('jobs');
        Route::get('/team', [\App\Http\Controllers\ProductionController::class, 'team'])->name('team');
        Route::post('/team/assign', [\App\Http\Controllers\ProductionController::class, 'assignTeamMember'])->name('team.assign');
        Route::post('/team/head', [\App\Http\Controllers\ProductionController::class, 'setTeamHead'])->name('team.head');
        Route::delete('/team/{assignment}', [\App\Http\Controllers\ProductionController::class, 'removeTeamMember'])->name('team.remove');
        Route::get('/create', [\App\Http\Controllers\ProductionController::class, 'create'])->name('create');
        Route::get('/start-job', [\App\Http\Controllers\ProductionController::class, 'startJobForm'])->name('start-job');
        Route::post('/start-job', [\App\Http\Controllers\ProductionController::class, 'startJobStore'])->name('start-job.store');
        Route::get('/start-job/whatsapp-search', [\App\Http\Controllers\ProductionController::class, 'searchWhatsappChats'])->name('start-job.whatsapp-search');
        Route::get('/materials/search', [\App\Http\Controllers\ProductionController::class, 'searchInventoryMaterials'])->name('materials.search-global');
        Route::post('/', [\App\Http\Controllers\ProductionController::class, 'store'])->name('store');
        Route::get('/section/{stage}', [\App\Http\Controllers\ProductionController::class, 'sectionDashboard'])->name('section');
        Route::get('/products/search-convert', [\App\Http\Controllers\ProductionController::class, 'searchProductsForConvert'])->name('products.search');
        Route::get('/{job}/convert-product', [\App\Http\Controllers\ProductionController::class, 'convertProductForm'])->name('convert.form');
        Route::post('/{job}/convert-product', [\App\Http\Controllers\ProductionController::class, 'convertToProduct'])->name('convert');
        Route::delete('/files/{file}', [\App\Http\Controllers\ProductionController::class, 'deleteFile'])->name('file.delete');
        Route::get('/files/{file}/download', [\App\Http\Controllers\ProductionController::class, 'downloadFile'])->name('file.download');
        Route::delete('/{job}/materials/{usage}', [\App\Http\Controllers\ProductionController::class, 'removeMaterial'])->name('materials.remove');
        Route::get('/{job}', [\App\Http\Controllers\ProductionController::class, 'show'])->name('show');
        Route::get('/{job}/edit', [\App\Http\Controllers\ProductionController::class, 'edit'])->name('edit');
        Route::get('/{job}/detail', [\App\Http\Controllers\ProductionController::class, 'adminDetail'])->name('detail');
        Route::put('/{job}', [\App\Http\Controllers\ProductionController::class, 'update'])->name('update');
        Route::post('/{job}/advance', [\App\Http\Controllers\ProductionController::class, 'advance'])->name('advance');
        Route::post('/{job}/task/start', [\App\Http\Controllers\ProductionController::class, 'taskStart'])->name('task.start');
        Route::post('/{job}/task/end', [\App\Http\Controllers\ProductionController::class, 'taskEnd'])->name('task.end');
        Route::get('/{job}/materials/search', [\App\Http\Controllers\ProductionController::class, 'searchJobMaterials'])->name('materials.search');
        Route::post('/{job}/materials', [\App\Http\Controllers\ProductionController::class, 'addMaterial'])->name('materials.add');
        Route::post('/{job}/files', [\App\Http\Controllers\ProductionController::class, 'uploadFiles'])->name('files.upload');
        Route::post('/{job}/drive', [\App\Http\Controllers\ProductionController::class, 'updateDrive'])->name('drive.update');
    });

    // ── Employee Weekly To-Do ────────────────────────────────────────────────
    Route::prefix('employee-todos')->name('employee-todos.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EmployeeTodoController::class, 'index'])->name('index');
        Route::get('/my-week', [\App\Http\Controllers\EmployeeTodoController::class, 'myWeek'])->name('my-week');

        Route::get('/categories', [\App\Http\Controllers\TaskCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [\App\Http\Controllers\TaskCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [\App\Http\Controllers\TaskCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [\App\Http\Controllers\TaskCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/reorder', [\App\Http\Controllers\TaskCategoryController::class, 'reorder'])->name('categories.reorder');

        Route::get('/templates', [\App\Http\Controllers\WeeklyPlanTemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [\App\Http\Controllers\WeeklyPlanTemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [\App\Http\Controllers\WeeklyPlanTemplateController::class, 'store'])->name('templates.store');
        Route::get('/templates/{template}/edit', [\App\Http\Controllers\WeeklyPlanTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('/templates/{template}', [\App\Http\Controllers\WeeklyPlanTemplateController::class, 'update'])->name('templates.update');
        Route::delete('/templates/{template}', [\App\Http\Controllers\WeeklyPlanTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('/templates/{template}/duplicate', [\App\Http\Controllers\WeeklyPlanTemplateController::class, 'duplicate'])->name('templates.duplicate');

        Route::post('/items', [\App\Http\Controllers\EmployeeTodoController::class, 'storeItem'])->name('items.store');
        Route::post('/items/{item}/toggle', [\App\Http\Controllers\EmployeeTodoController::class, 'toggleItem'])->name('items.toggle');
        Route::delete('/items/{item}', [\App\Http\Controllers\EmployeeTodoController::class, 'deleteItem'])->name('items.delete');
        Route::post('/assign-template', [\App\Http\Controllers\EmployeeTodoController::class, 'assignTemplate'])->name('assign-template');
        Route::post('/copy-week', [\App\Http\Controllers\EmployeeTodoController::class, 'copyWeek'])->name('copy-week');
        Route::post('/plan-notes', [\App\Http\Controllers\EmployeeTodoController::class, 'updatePlanNotes'])->name('plan-notes');
    });
});

// Route::middleware(['EcomApi'])->prefix('api/ecom')->group(function () {
//     Route::get('products/{id?}', [ProductController::class, 'getProductsApi']);
//     Route::get('categories', [CategoryController::class, 'getCategoriesApi']);
//     Route::get('brands', [BrandController::class, 'getBrandsApi']);
//     Route::post('customers', [ContactController::class, 'postCustomersApi']);
//     Route::get('settings', [BusinessController::class, 'getEcomSettings']);
//     Route::get('variations', [ProductController::class, 'getVariationsApi']);
//     Route::post('orders', [SellPosController::class, 'placeOrdersApi']);
// });

//common route
Route::middleware(['auth'])->group(function () {
    Route::get('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout.get');
});

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/load-more-notifications', [HomeController::class, 'loadMoreNotifications']);
    Route::get('/get-total-unread', [HomeController::class, 'getTotalUnreadNotifications']);
    Route::get('/purchases/print/{id}', [PurchaseController::class, 'printInvoice']);
    Route::get('/purchases/{id}', [PurchaseController::class, 'show']);
    Route::get('/download-purchase-order/{id}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('purchaseOrder.downloadPdf');
    Route::get('/sells/{id}', [SellController::class, 'show']);
    Route::get('/sells/{transaction_id}/print', [SellPosController::class, 'printInvoice'])->name('sell.printInvoice');
    Route::get('/download-sells/{transaction_id}/pdf', [SellPosController::class, 'downloadPdf'])->name('sell.downloadPdf');
    Route::get('/download-quotation/{id}/pdf', [SellPosController::class, 'downloadQuotationPdf'])
        ->name('quotation.downloadPdf');
    Route::get('/download-packing-list/{id}/pdf', [SellPosController::class, 'downloadPackingListPdf'])
        ->name('packing.downloadPdf');
    Route::get('/sells/invoice-url/{id}', [SellPosController::class, 'showInvoiceUrl']);
    Route::get('/show-notification/{id}', [HomeController::class, 'showNotification']);
    Route::post('/sell/check-invoice-number', [SellController::class, 'checkInvoiceNumber']);
});


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Route::get('/deploy-run', function () {
    if (trim((string) request('key', '')) !== '123') {
        abort(403, 'Invalid deploy key.');
    }

    $output = [];
    $errors = [];

    $run = function (string $label, string $command, array $params = []) use (&$output, &$errors) {
        try {
            Artisan::call($command, $params);
            $result = trim(Artisan::output());
            $output[] = 'OK ['.$label.']: '.($result !== '' ? $result : 'Done');
        } catch (\Throwable $e) {
            $errors[] = 'ERROR ['.$label.']: '.$e->getMessage();
        }
    };

    $run('optimize:clear', 'optimize:clear');
    $run('migrate', 'migrate', ['--force' => true]);

    $modules = [
        'Accounting',
        'AssetManagement',
        'Cms',
        'Crm',
        'Essentials',
        'InventoryManagement',
    ];

    foreach ($modules as $module) {
        $path = "Modules/{$module}/Database/Migrations";
        if (is_dir(base_path($path))) {
            $run("migrate:{$module}", 'migrate', [
                '--path' => $path,
                '--force' => true,
            ]);
        } else {
            $output[] = "SKIP [migrate:{$module}]: folder not found";
        }
    }

    $run('db:seed WalkInCustomer', 'db:seed', [
        '--class' => 'Database\\Seeders\\WalkInCustomerSeeder',
        '--force' => true,
    ]);

    try {
        $layoutCount = DB::table('invoice_layouts')->update(['design' => 'thermal']);
        $output[] = 'OK [invoice_layouts]: design set to thermal ('.$layoutCount.' row(s))';
    } catch (\Throwable $e) {
        $errors[] = 'ERROR [invoice_layouts]: '.$e->getMessage();
    }

    $run('storage:link', 'storage:link');
    $run('config:cache', 'config:cache');
    $run('view:cache', 'view:cache');

    $response = "=== MILLENNIUM AGRO DEPLOY ===\n";
    $response .= 'URL: '.config('app.url')."\n\n";
    $response .= implode("\n", $output);

    if (! empty($errors)) {
        $response .= "\n\n=== ERRORS ===\n".implode("\n", $errors);
    } else {
        $response .= "\n\nAll steps completed.";
        $response .= "\nLogin: ".url('/login');
    }

    return response($response)->header('Content-Type', 'text/plain; charset=UTF-8');
});