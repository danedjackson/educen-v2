<?php

namespace Database\Seeders;

use App\Models\AssignmentType;
use Illuminate\Database\Seeder;

class AssignmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Homework',
            'Classwork',
            'Unit Test',
            'Mid Term Examination',
            'End of Year Examination',
            'End of Term Examination',
            'Project',
            'Quiz',
            'Performance Task',
            'Mock Exam',
            'Portfolio',
            'Diagnostic Assessment',
            'IDRI',
            'GOILP',
            'Standardized Exam',
        ];

        foreach ($types as $name) {
            AssignmentType::firstOrCreate(['name' => $name]);
        }
    }
}
