<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfessionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $professions = [
            [
                'key' => 'advocate',
                'name_ru' => 'Адвокат',
                'name_kk' => 'Адвокат',
                'name_en' => 'Advocate',
                'name_zh' => '律师',
                'description' => 'Legal professional who represents clients in court',
                'is_for_expert' => true,
                'is_for_lawyer' => true,
                'status' => 'active'
            ],
            [
                'key' => 'notary',
                'name_ru' => 'Нотариус',
                'name_kk' => 'Нотариус',
                'name_en' => 'Notary',
                'name_zh' => '公证员',
                'description' => 'Public official who witnesses and certifies documents',
                'is_for_expert' => true,
                'is_for_lawyer' => true,
                'status' => 'active'
            ],
            [
                'key' => 'auditor',
                'name_ru' => 'Аудитор',
                'name_kk' => 'Аудитор',
                'name_en' => 'Auditor',
                'name_zh' => '审计师',
                'description' => 'Financial professional who examines and verifies accounts',
                'is_for_expert' => true,
                'is_for_lawyer' => false,
                'status' => 'active'
            ],
            [
                'key' => 'tax_consultant',
                'name_ru' => 'Налоговый консультант',
                'name_kk' => 'Салық кеңесшісі',
                'name_en' => 'Tax Consultant',
                'name_zh' => '税务顾问',
                'description' => 'Specialist in tax law and regulations',
                'is_for_expert' => true,
                'is_for_lawyer' => false,
                'status' => 'active'
            ],
            [
                'key' => 'corporate_lawyer',
                'name_ru' => 'Корпоративный юрист',
                'name_kk' => 'Корпоративтік заңгер',
                'name_en' => 'Corporate Lawyer',
                'name_zh' => '公司法务',
                'description' => 'Legal professional specializing in corporate law',
                'is_for_expert' => false,
                'is_for_lawyer' => true,
                'status' => 'active'
            ],
            [
                'key' => 'criminal_lawyer',
                'name_ru' => 'Уголовный адвокат',
                'name_kk' => 'Қылмыстық адвокат',
                'name_en' => 'Criminal Lawyer',
                'name_zh' => '刑事律师',
                'description' => 'Legal professional specializing in criminal defense',
                'is_for_expert' => false,
                'is_for_lawyer' => true,
                'status' => 'active'
            ],
            [
                'key' => 'family_lawyer',
                'name_ru' => 'Семейный юрист',
                'name_kk' => 'Отбасылық заңгер',
                'name_en' => 'Family Lawyer',
                'name_zh' => '家庭律师',
                'description' => 'Legal professional specializing in family law',
                'is_for_expert' => false,
                'is_for_lawyer' => true,
                'status' => 'active'
            ],
            [
                'key' => 'immigration_lawyer',
                'name_ru' => 'Иммиграционный юрист',
                'name_kk' => 'Иммиграциялық заңгер',
                'name_en' => 'Immigration Lawyer',
                'name_zh' => '移民律师',
                'description' => 'Legal professional specializing in immigration law',
                'is_for_expert' => false,
                'is_for_lawyer' => true,
                'status' => 'active'
            ]
        ];

        foreach ($professions as $profession) {
            DB::table('professions')->insert(array_merge($profession, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
}
