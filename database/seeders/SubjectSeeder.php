<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        
        // Clear the subjects table first
        DB::table('subjects')->truncate();
        
        // Compulsory subjects (for all groups)
        $compulsorySubjects = [
            ['subject_name' => 'Bangla', 'subject_category' => 'compulsory', 'subject_code' => 'BAN101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'English', 'subject_category' => 'compulsory', 'subject_code' => 'ENG101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Mathematics', 'subject_category' => 'compulsory', 'subject_code' => 'MAT101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Religion', 'subject_category' => 'compulsory', 'subject_code' => 'REL101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'ICT', 'subject_category' => 'compulsory', 'subject_code' => 'ICT101', 'full_marks' => 50, 'pass_marks' => 17],
        ];
        
        // Science group subjects
        $scienceSubjects = [
            ['subject_name' => 'Physics', 'subject_category' => 'science', 'subject_code' => 'PHY101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Chemistry', 'subject_category' => 'science', 'subject_code' => 'CHE101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Biology', 'subject_category' => 'science', 'subject_code' => 'BIO101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Higher Mathematics', 'subject_category' => 'science', 'subject_code' => 'HMT101', 'full_marks' => 100, 'pass_marks' => 33],
        ];
        
        // Commerce group subjects
        $commerceSubjects = [
            ['subject_name' => 'Accounting', 'subject_category' => 'commerce', 'subject_code' => 'ACC101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Business Studies', 'subject_category' => 'commerce', 'subject_code' => 'BUS101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Finance', 'subject_category' => 'commerce', 'subject_code' => 'FIN101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Economics', 'subject_category' => 'commerce', 'subject_code' => 'ECO101', 'full_marks' => 100, 'pass_marks' => 33],
        ];
        
        // Arts group subjects
        $artsSubjects = [
            ['subject_name' => 'History', 'subject_category' => 'arts', 'subject_code' => 'HIS101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Geography', 'subject_category' => 'arts', 'subject_code' => 'GEO101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Civics', 'subject_category' => 'arts', 'subject_code' => 'CIV101', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Economics', 'subject_category' => 'arts', 'subject_code' => 'ECO102', 'full_marks' => 100, 'pass_marks' => 33],
            ['subject_name' => 'Sociology', 'subject_category' => 'arts', 'subject_code' => 'SOC101', 'full_marks' => 100, 'pass_marks' => 33],
        ];
        
        // Insert all subjects
        $allSubjects = array_merge(
            $compulsorySubjects,
            $scienceSubjects,
            $commerceSubjects,
            $artsSubjects
        );
        
        foreach ($allSubjects as $subject) {
            $subject['created_at'] = now();
            $subject['updated_at'] = now();
            
            // Skip if subject already exists (based on name and category)
            $exists = DB::table('subjects')
                ->where('subject_name', $subject['subject_name'])
                ->where('subject_category', $subject['subject_category'])
                ->exists();
                
            if (!$exists) {
                DB::table('subjects')->insert($subject);
            }
        }
        
        Schema::enableForeignKeyConstraints();
        
        $this->command->info('Subjects seeded successfully!');
    }
} 