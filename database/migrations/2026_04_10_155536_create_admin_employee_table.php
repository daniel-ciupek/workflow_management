<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_employee', function (Blueprint $table) {
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['admin_id', 'employee_id']);
        });

        // Migrate existing admin_id data → pivot
        $superAdminId = DB::table('users')->where('is_super', true)->value('id');
        $employees = DB::table('users')->where('role', 'employee')->whereNotNull('admin_id')->get();

        foreach ($employees as $emp) {
            DB::table('admin_employee')->insertOrIgnore([
                'admin_id'    => $emp->admin_id,
                'employee_id' => $emp->id,
            ]);
            if ($superAdminId && $superAdminId !== $emp->admin_id) {
                DB::table('admin_employee')->insertOrIgnore([
                    'admin_id'    => $superAdminId,
                    'employee_id' => $emp->id,
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn('admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('admin_id')->nullable()->after('role')->constrained('users')->nullOnDelete();
        });

        // Restore admin_id from pivot (first admin per employee)
        $rows = DB::table('admin_employee')->get();
        foreach ($rows as $row) {
            DB::table('users')
                ->where('id', $row->employee_id)
                ->whereNull('admin_id')
                ->update(['admin_id' => $row->admin_id]);
        }

        Schema::dropIfExists('admin_employee');
    }
};
