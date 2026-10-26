<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('fee_categories')->where('name', 'Tuition Fee')->exists()) {
            DB::table('fee_categories')->where('code', 'monthly')->update([
                'name' => 'Tuition Fee',
                'updated_at' => now(),
            ]);
        }
        if (! DB::table('fee_categories')->where('code', 'custom-fee')->orWhere('name', 'Custom Fee')->exists()) {
            DB::table('fee_categories')->insert([
                'name' => 'Custom Fee',
                'code' => 'custom-fee',
                'is_recurring' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! DB::table('fee_categories')->where('name', 'Monthly Fee')->exists()) {
            DB::table('fee_categories')->where('code', 'monthly')->update([
                'name' => 'Monthly Fee',
                'updated_at' => now(),
            ]);
        }
    }
};
