<?php

use App\Business;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up()
    {
        Schema::create('production_stage_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('from_stage', 40);
            $table->string('to_stage', 40);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('requested_by');
            $table->text('notes')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('production_jobs')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'created_at']);
            $table->index(['job_id', 'status']);
        });

        $permission = Permission::firstOrCreate(
            ['name' => 'production.manager', 'guard_name' => 'web']
        );
        Permission::firstOrCreate(
            ['name' => 'production.access', 'guard_name' => 'web']
        );

        foreach (Business::query()->pluck('id') as $businessId) {
            $role = Role::firstOrCreate(
                [
                    'name' => 'Production Manager#'.$businessId,
                    'guard_name' => 'web',
                ],
                [
                    'business_id' => $businessId,
                    'is_default' => 0,
                ]
            );
            $role->givePermissionTo(['production.manager', 'production.access']);
        }
    }

    public function down()
    {
        Schema::dropIfExists('production_stage_approvals');

        foreach (Business::query()->pluck('id') as $businessId) {
            $role = Role::where('name', 'Production Manager#'.$businessId)->first();
            if ($role) {
                $role->delete();
            }
        }

        Permission::where('name', 'production.manager')->delete();
    }
};
