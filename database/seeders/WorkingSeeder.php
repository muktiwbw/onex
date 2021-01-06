<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\AnswerSheet;
use App\Models\Level;
use App\Models\User;
use App\Models\Answer;

use Illuminate\Support\Arr;

class WorkingSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // Get levels and users
    $levels = Level::all();
    $users = User::where('role', '<>', 'SUPERADMIN')->get();

    // Initiate possible answers for multiple choice and checklist
    $points = range('a', 'e');
    $answer = array_merge(range(1, 5), [null]);

    foreach ($levels as $level) {
      foreach ($users as $user) {
        // Initiate answer sheet
        $answerSheet = AnswerSheet::factory()
                                  ->create([
                                    'level_id' => $level->id,
                                    'user_id' => $user->id
                                  ]);

        // Get all questions
        $questions = $level->questions()
                          ->orderBy('number', 'asc')
                          ->get();

        foreach ($questions as $question) {
          // Initiate mandatory fields
          $fields = [
            'answer_sheet_id' => $answerSheet->id,
            'question_id' => $question->id
          ];

          // If question type either multiple or checklist,
          //  assign essay field to null
          if ($question->type === 'MULTIPLE') {
            // Get random point from point pool,
            //  and merge additional fields to mandatory fields
            $fields = array_merge($fields, [
              'essay' => null,
              'point' => Arr::random($points)
            ]);
          } elseif ($question->type === 'CHECKLIST') {
            // Get all checklists from question
            $checklists = $question->checklists()->orderBy('number', 'asc')->get();

            // Map checklist to [{"number": "x", "answer": "y"}, {...}]
            $jsonChecklist = json_encode(
              $checklists->map(function ($checklist) use ($answer) {
                return [
                  'number' => $checklist->number,
                  'answer' => Arr::random($answer)
                ];
              })
            );

            // Merge initial fields with mandatory fields
            $fields = array_merge($fields, [
              'essay' => null,
              'checklist' => $jsonChecklist
            ]);
          }

          // Submit answer
          Answer::factory()->create($fields);
        }
      }
    }
  }
}
