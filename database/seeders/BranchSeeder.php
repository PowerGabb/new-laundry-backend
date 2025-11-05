<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->limit(3)->get();

        if ($users->isEmpty()) {
            $users = User::factory()->count(3)->create();
        }

        foreach ($users as $user) {
            Branch::factory()->count(rand(2, 5))->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
