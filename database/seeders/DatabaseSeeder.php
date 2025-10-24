<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Membership;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $members = Member::factory(40)->create();

        foreach ($members as $member) {
            Membership::factory()
                ->count(3)
                ->state([
                    'member_id' => $member->id
                ])
                ->create();
        }
    }
}
