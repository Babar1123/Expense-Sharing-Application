<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Group;
use App\Models\Settlement;
use App\Models\Token;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->delete();
        Group::query()->delete();
        Expense::query()->delete();
        Settlement::query()->delete();
        Token::query()->delete();

        $users = [];
        foreach (range(1, 10) as $index) {
            $user = User::create([
                'name' => 'User '.$index,
                'email' => 'user'.$index.'@example.com',
                'password' => bcrypt('password123'),
            ]);

            $users[] = $user;

            Token::create([
                'user_id' => $user->_id,
                'token' => 'seed-token-'.$index.'-'.Str::random(20),
                'revoked_at' => null,
            ]);
        }

        $group = Group::create([
            'name' => 'Dubai Trip',
            'description' => 'Trip expenses',
            'owner_id' => (string) $users[0]->_id,
            'member_ids' => array_map(fn ($user) => (string) $user->_id, array_slice($users, 0, 4)),
        ]);

        Expense::create([
            'group_id' => (string) $group->_id,
            'description' => 'Dinner',
            'amount' => 12000,
            'paid_by' => (string) $users[0]->_id,
            'split_type' => 'equal',
            'participants' => [
                ['user_id' => (string) $users[0]->_id, 'amount' => 3000],
                ['user_id' => (string) $users[1]->_id, 'amount' => 3000],
                ['user_id' => (string) $users[2]->_id, 'amount' => 3000],
                ['user_id' => (string) $users[3]->_id, 'amount' => 3000],
            ],
            'created_by' => (string) $users[0]->_id,
        ]);

        Settlement::create([
            'group_id' => (string) $group->_id,
            'paid_by' => (string) $users[1]->_id,
            'paid_to' => (string) $users[0]->_id,
            'amount' => 3000,
            'note' => 'Dinner settlement',
        ]);
    }
}
