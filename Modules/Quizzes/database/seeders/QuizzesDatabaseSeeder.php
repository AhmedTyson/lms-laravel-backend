<?php

namespace Modules\Quizzes\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Courses\Models\Course;
use Modules\Quizzes\Models\Attempt;
use Modules\Quizzes\Models\Question;
use Modules\Quizzes\Models\QuestionOption;
use Modules\Quizzes\Models\Quiz;

class QuizzesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $course = Course::first() ?? Course::factory()->create();
        $quiz = Quiz::factory()->create(['course_id' => $course->id]);

        $choice = Question::factory()->create([
            'quiz_id' => $quiz->id, 'type' => 'single_choice', 'order' => 1,
        ]);
        QuestionOption::factory()->create(['question_id' => $choice->id, 'is_correct' => true]);
        QuestionOption::factory()->create(['question_id' => $choice->id, 'is_correct' => false]);

        Question::factory()->create([
            'quiz_id' => $quiz->id, 'type' => 'true_false', 'order' => 2,
            'correct_boolean' => true,
        ]);

        Attempt::factory()->create(['quiz_id' => $quiz->id]);
    }
}
