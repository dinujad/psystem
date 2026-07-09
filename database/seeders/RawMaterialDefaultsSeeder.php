<?php

namespace Database\Seeders;

use App\InventoryCategory;
use App\InventoryUnit;
use Illuminate\Database\Seeder;

class RawMaterialDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        if (InventoryUnit::count() === 0) {
            $units = [
                ['name' => 'Pieces', 'abbreviation' => 'pcs'],
                ['name' => 'Sheets', 'abbreviation' => 'sht'],
                ['name' => 'Kilograms', 'abbreviation' => 'kg'],
                ['name' => 'Grams', 'abbreviation' => 'g'],
                ['name' => 'Meters', 'abbreviation' => 'm'],
                ['name' => 'Square Meters', 'abbreviation' => 'sqm'],
                ['name' => 'Litres', 'abbreviation' => 'L'],
                ['name' => 'Rolls', 'abbreviation' => 'roll'],
                ['name' => 'Boxes', 'abbreviation' => 'box'],
                ['name' => 'Reams', 'abbreviation' => 'ream'],
            ];
            foreach ($units as $u) {
                InventoryUnit::create($u);
            }
        }

        if (InventoryCategory::count() === 0) {
            $categories = [
                ['name' => 'Paper & Board', 'description' => 'Art paper, card, board, labels'],
                ['name' => 'Ink & Toner', 'description' => 'Printing inks, toners, cartridges'],
                ['name' => 'Lamination & Film', 'description' => 'Lamination rolls, OPP, BOPP'],
                ['name' => 'Binding & Finishing', 'description' => 'Spiral, glue, staples, cutting blades'],
                ['name' => 'Chemicals & Consumables', 'description' => 'Developer, cleaner, gloves'],
                ['name' => 'Packaging', 'description' => 'Boxes, bags, wrapping materials'],
                ['name' => 'Other', 'description' => 'Miscellaneous raw materials'],
            ];
            foreach ($categories as $c) {
                InventoryCategory::create($c);
            }
        }
    }
}
