<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use App\Models\NannySlot;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\UserLanguage;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Parent Account
        $parent = User::firstOrCreate(
            ['phone' => '+77011111111'],
            [
                'role' => UserRole::PARENT,
                'status' => UserStatus::ACTIVE,
                'language' => UserLanguage::RU,
            ]
        );
        $parent->profile()->updateOrCreate(
            ['user_id' => $parent->id],
            [
                'first_name' => 'Аслан',
                'last_name' => 'Асланов',
                'city' => 'Алматы',
                'balance_coins' => 0,
                'is_verified' => false,
                'latitude' => 43.238949,
                'longitude' => 76.889709,
            ]
        );

        // 1b. Create Astana Parent Account
        $astanaParent = User::firstOrCreate(
            ['phone' => '+77011000000'],
            [
                'role' => UserRole::PARENT,
                'status' => UserStatus::ACTIVE,
                'language' => UserLanguage::RU,
            ]
        );
        $astanaParent->profile()->updateOrCreate(
            ['user_id' => $astanaParent->id],
            [
                'first_name' => 'Арман',
                'last_name' => 'Нурланов',
                'city' => 'Астана',
                'balance_coins' => 0,
                'is_verified' => false,
                'latitude' => 51.169392,
                'longitude' => 71.449074,
            ]
        );

        // 2. Main Nannies Data
        $nanniesData = [
            [
                'phone' => '+77012222222',
                'first_name' => 'Айгерим',
                'last_name' => 'Саматова',
                'bio' => 'Опытная няня со стажем более 5 лет. Люблю детей, имею медицинское образование.',
                'bio_kk' => '5 жылдан астам тәжірибесі бар білікті бала күтуші. Медициналық білімім бар.',
                'avatar_url' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 2000,
                'experience' => 5,
                'is_verified' => true,
                'lat' => 43.235519,
                'lng' => 76.909930,
                'languages' => ['ru', 'kk'],
                'skills' => ['first_aid', 'infants'],
            ],
            [
                'phone' => '+77013333333',
                'first_name' => 'Камила',
                'last_name' => 'Темирова',
                'bio' => 'Студентка педагогического университета. Ищу подработку няней.',
                'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 1200,
                'experience' => 1,
                'is_verified' => true,
                'lat' => 43.250000,
                'lng' => 76.900000,
                'languages' => ['ru', 'en'],
                'skills' => ['lessons'],
            ],
            [
                'phone' => '+77015555555',
                'first_name' => 'Алия',
                'last_name' => 'Нурахметова',
                'bio' => 'Заботливая няня для самых маленьких. Опыт в детских садах 4 года.',
                'avatar_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 1800,
                'experience' => 4,
                'is_verified' => true,
                'lat' => 43.242000,
                'lng' => 76.875000,
                'languages' => ['kk', 'ru'],
                'skills' => ['infants', 'first_aid'],
            ],
            [
                'phone' => '+77016666666',
                'first_name' => 'Гульнара',
                'last_name' => 'Искакова',
                'bio' => 'Англоязычная няня. Обучаю детей в игровой форме по методике Монтессори.',
                'avatar_url' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 2500,
                'experience' => 6,
                'is_verified' => true,
                'lat' => 43.265000,
                'lng' => 76.915000,
                'languages' => ['ru', 'en'],
                'skills' => ['montessori', 'lessons'],
            ],
            [
                'phone' => '+77017777777',
                'first_name' => 'Светлана',
                'last_name' => 'Козлова',
                'bio' => 'Люблю активные прогулки и развивающие игры. Есть рекомендации.',
                'avatar_url' => 'https://images.unsplash.com/photo-1567532939604-b6b5b0db2604?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 1700,
                'experience' => 3,
                'is_verified' => true,
                'lat' => 43.220000,
                'lng' => 76.970000,
                'languages' => ['ru'],
                'skills' => ['lessons', 'first_aid'],
            ],
            [
                'phone' => '+77018888888',
                'first_name' => 'Зарина',
                'last_name' => 'Султанова',
                'bio' => 'Ответственная, пунктуальная няня. Высшее педагогическое образование.',
                'avatar_url' => 'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 2200,
                'experience' => 8,
                'is_verified' => true,
                'lat' => 43.290000,
                'lng' => 76.780000,
                'languages' => ['ru', 'kk', 'en'],
                'skills' => ['first_aid', 'montessori', 'lessons'],
            ],
            [
                'phone' => '+77019999999',
                'first_name' => 'Динара',
                'last_name' => 'Утепова',
                'bio' => 'Няня на выходные и вечернее время. Быстро нахожу подход к детям.',
                'avatar_url' => 'https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 1500,
                'experience' => 2,
                'is_verified' => true,
                'city' => 'Алматы',
                'lat' => 43.150000,
                'lng' => 76.750000,
                'languages' => ['ru', 'kk'],
                'skills' => ['infants'],
            ],
            // Astana Nannies
            [
                'phone' => '+77011000001',
                'first_name' => 'Мадина',
                'last_name' => 'Ержанова',
                'bio' => 'Опытная няня в Астане. Высшее образование, свободный казахский и русский.',
                'avatar_url' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 2200,
                'experience' => 5,
                'is_verified' => true,
                'city' => 'Астана',
                'lat' => 51.169392,
                'lng' => 71.449074,
                'languages' => ['kk', 'ru'],
                'skills' => ['first_aid', 'montessori'],
            ],
            [
                'phone' => '+77011000002',
                'first_name' => 'Дана',
                'last_name' => 'Нурланова',
                'bio' => 'Няня-гувернантка в Астане (Левый берег). Помощь с уроками и английским.',
                'avatar_url' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 2600,
                'experience' => 4,
                'is_verified' => true,
                'city' => 'Астана',
                'lat' => 51.130000,
                'lng' => 71.420000,
                'languages' => ['ru', 'en'],
                'skills' => ['lessons'],
            ],
            [
                'phone' => '+77011000003',
                'first_name' => 'Салтанат',
                'last_name' => 'Ахметова',
                'bio' => 'Профессиональная няня для детей от 0 до 6 лет в Астане.',
                'avatar_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=300&auto=format&fit=crop&q=80',
                'hourly_rate' => 2000,
                'experience' => 7,
                'is_verified' => true,
                'city' => 'Астана',
                'lat' => 51.150000,
                'lng' => 71.470000,
                'languages' => ['kk', 'ru'],
                'skills' => ['infants', 'first_aid'],
            ],
        ];

        // 3. Admin User
        $admin = User::firstOrCreate(
            ['phone' => '+77014444444'],
            [
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'language' => UserLanguage::RU,
            ]
        );
        $admin->profile()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'first_name' => 'Администратор',
                'last_name' => 'Системы',
                'balance_coins' => 0,
                'is_verified' => false,
            ]
        );

        // 4. Populate Nannies & 30 Days of Availability Slots
        foreach ($nanniesData as $data) {
            $user = User::firstOrCreate(
                ['phone' => $data['phone']],
                [
                    'role' => UserRole::NANNY,
                    'status' => UserStatus::ACTIVE,
                    'language' => UserLanguage::RU,
                ]
            );

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'city' => $data['city'] ?? 'Алматы',
                    'avatar_url' => $data['avatar_url'] ?? null,
                    'bio' => $data['bio'],
                    'bio_kk' => $data['bio_kk'] ?? null,
                    'hourly_rate' => $data['hourly_rate'],
                    'experience_years' => $data['experience'],
                    'balance_coins' => 2500,
                    'is_verified' => $data['is_verified'],
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                    'languages' => $data['languages'],
                    'skills' => $data['skills'],
                ]
            );

            // Generate daily availability slots for 30 days into the future
            for ($day = 0; $day < 30; $day++) {
                $targetDate = Carbon::today()->addDays($day);

                // Slot 1: Morning (09:00 - 13:00)
                NannySlot::updateOrCreate(
                    [
                        'nanny_id' => $user->id,
                        'start_time' => $targetDate->copy()->setHour(9)->setMinute(0)->format('Y-m-d H:i:s'),
                    ],
                    [
                        'end_time' => $targetDate->copy()->setHour(13)->setMinute(0)->format('Y-m-d H:i:s'),
                        'status' => 'available',
                    ]
                );

                // Slot 2: Afternoon (14:00 - 18:00)
                NannySlot::updateOrCreate(
                    [
                        'nanny_id' => $user->id,
                        'start_time' => $targetDate->copy()->setHour(14)->setMinute(0)->format('Y-m-d H:i:s'),
                    ],
                    [
                        'end_time' => $targetDate->copy()->setHour(18)->setMinute(0)->format('Y-m-d H:i:s'),
                        'status' => 'available',
                    ]
                );
            }
        }
    }
}
