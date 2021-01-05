<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      // Generating level names
      $names = ['Beginner I', 'Beginner II', 'Intermediete', 'Advance'];

      foreach ($names as $name) {
        // Running level factory
        $level = \App\Models\Level::factory()->create([
          'name' => $name
        ]);
  
        foreach (range(1, 10) as $number) {
          // Assigning question types based on numbers
          $type;
          
          /**
           * note:
           *  for some reasone, when being used as an argument in array_search, range() is exclusive for the bottom range.
           */
          if (array_search($number, range(0,5))) {
            $type = 'MULTIPLE';
          } elseif (array_search($number, range(5,9))) {
            $type = 'ESSAY';
          } else {
            $type = 'CHECKLIST';
          }
  
          // Generating fields
          $qFields = [
            'number' => $number,
            'type' => $type
          ];
  
          // Overriding question factory value
          if ($type !== 'ESSAY') {
            $qFields['essay'] = null;
          }
  
          // Running Question Factory
          $question = \App\Models\Question::factory()
                                          ->for($level)
                                          ->create($qFields);
  
          // Handling question items for non-essay
          if ($type === 'MULTIPLE') {
            // Running choice factory
            foreach (range('a', 'e') as $point) {
              \App\Models\Choice::factory()
                                ->for($question)
                                ->create([
                                  'point' => $point,
                                  'isCorrect' => $point === 'd' ? true : false
                                ]);
            }
          } elseif ($type === 'CHECKLIST') {
            // Running checklist factory
            foreach (range(1, 6) as $number) {
              \App\Models\Checklist::factory()
                                   ->for($question)
                                   ->create([
                                     'number' => $number
                                   ]);
            }
          }
        }
      }
    }
}
