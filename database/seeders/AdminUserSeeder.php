<?php

namespace Database\Seeders;

use App\Business;
use App\BusinessLocation;
use App\User;
use App\Utils\BusinessUtil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $adminEmail = 'admin@printworks.lk';

        $existing = User::where('username', 'admin')->first();
        if ($existing) {
            if ($existing->email !== $adminEmail) {
                $existing->update(['email' => $adminEmail]);
                $this->command?->info("Admin email updated to {$adminEmail}");
            }

            return;
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'surname' => 'Mr',
                'first_name' => 'Admin',
                'username' => 'admin',
                'email' => $adminEmail,
                'password' => Hash::make('Admin2026@@'), // Default given password
                'language' => 'en',
                // business.owner_id is a required FK to users.id,
                // so we create the user first and link later.
                'business_id' => null,
                'is_cmmsn_agnt' => 0,
                'cmmsn_percent' => 0
            ]);

            $business = Business::create([
                'name' => 'My Business',
                'currency_id' => 2, // Default USD
                'start_date' => date('Y-m-d'),
                'time_zone' => 'UTC',
                'fy_start_month' => 1,
                'accounting_method' => 'fifo',
                'sell_price_tax' => 'includes',
                'keyboard_shortcuts' => '{"pos":{"express_checkout":"shift+e","pay_n_ckeckout":"shift+p","draft":"shift+d","cancel":"shift+c","edit_discount":"shift+i","edit_order_tax":"shift+t","add_payment_row":"shift+r","finalize_payment":"shift+f","recent_product_quantity":"f2","add_new_product":"f4"}}',
                'pos_settings' => '{"disable_pay_checkout":0,"disable_draft":0,"disable_express_checkout":0,"hide_product_suggestion":0,"hide_recent_trans":0,"disable_discount":0,"disable_order_tax":0}',
                'enable_brand' => 1,
                'enable_category' => 1,
                'enable_sub_category' => 1,
                'enable_price_tax' => 1,
                'enable_purchase_status' => 1,
                'enable_lot_number' => 0,
                'enable_racks' => 0,
                'enable_row' => 0,
                'enable_position' => 0,
                'enable_editing_product_from_purchase' => 1,
                'item_addition_method' => 1,
                'enable_inline_tax' => 0,
                'currency_symbol_placement' => 'before',
                'enabled_modules' => '["purchases","add_sale","pos_sale","stock_transfers","stock_adjustment","expenses","account"]',
                'date_format' => 'm/d/Y',
                'time_format' => '24',
                'ref_no_prefixes' => '{"purchase":"PO","stock_transfer":"ST","stock_adjustment":"SA","sell_return":"CN","expense":"EP","contacts":"CO","purchase_payment":"PP","sell_payment":"SP","business_location":"BL"}',
                'owner_id' => $user->id,
            ]);

            $user->business_id = $business->id;
            $user->save();

            $businessUtil = new BusinessUtil();
            $businessUtil->newBusinessDefaultResources($business->id, $user->id);

            $businessUtil->addLocation($business->id, [
                'name' => 'Default Location',
                'landmark' => '',
                'city' => '',
                'state' => '',
                'zip_code' => '',
                'country' => '',
                'mobile' => '',
                'alternate_number' => '',
                'website' => ''
            ]);

            DB::commit();
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
