<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Level;
use App\Models\CaseStudy;
use App\Models\Question;
use App\Models\Choice;
use App\Models\Checklist;

class ExamController extends Controller
{
  public function createLevels (Request $request) {
    $fn = function () use ($request) {

      $fields = $request->only('name', 'tujuan', 'uraian', 'examThreshold', 'evaluationThreshold');
      $fields['id'] = Str::uuid();

      $level = Level::create($fields);

      return res('Successfully created level', $level, 201);
    };

    return catcher($fn);
  }

  public function createCaseStudies (Request $request) {
    $fn = function () use ($request) {
      $fields = $request->only('number', 'title', 'body', 'type');
      $fields['id'] = Str::uuid();

      $caseStudy = CaseStudy::create($fields);

      if ($request->level_id) {
        $caseStudy->level_id = $request->level_id;
        $caseStudy->save();
      }

      return res('Successfully created case study', $caseStudy, 201);
    };
    
    return catcher($fn);
  }

  public function createQuestions (Request $request) {
    $fn = function () use ($request) {
      // Question
      $qFields = $request->only('body', 'type', 'essay', 'score');
      $qFields['id'] = Str::uuid();
      $qFields['level_id'] = $request->level_id;

      $level = Level::find($request->level_id);
      $lastQuestionAdded = $level->questions()->orderBy('number', 'desc')->first();
      $qFields['number'] = !$lastQuestionAdded ? 1 : $lastQuestionAdded->number + 1;

      $question = Question::create($qFields);
      
      if ($request->case_study_id) {
        $question->case_study_id = $request->case_study_id;
        $question->save();
      }

      if ($request->items) {
        // Question Items (Multiple Choice or Checklist)
        foreach ($request->items as $item) {
          $iFields = (array) json_decode($item);
          $iFields['id'] = Str::uuid();
          
          $model = $question->type == 'MULTIPLE' ? Choice::class : Checklist::class;
  
          $item = $model::create($iFields);
          $item->question()->associate($question);
          $item->save();
        }
      }
      
      return res('Successfully created question', $question, 201);
    };
    
    return catcher($fn);
  }

  public function updateLevels (Request $request) {
    $fn = function () use ($request) {
      $fields = $request->only('name', 'tujuan', 'uraian', 'examThreshold', 'evaluationThreshold');

      $level = Level::find($request->level_id);
      
      $level->update($fields);

      return res('Successfully updated level', $level, 201);
    };

    return catcher($fn);
  }
  
  public function updateCaseStudies (Request $request) {
    $fn = function () use ($request) {
      $fields = $request->only('number', 'title', 'body', 'type');

      $caseStudy = CaseStudy::find($request->case_study_id);

      $caseStudy->update($fields);

      return res('Successfully updated case study', $caseStudy, 201);
    };
    
    return catcher($fn);
  }

  public function updateQuestions (Request $request) {
    $fn = function () use ($request) {
      // Question
      $qFields = $request->only('body', 'essay', 'score');
      $question = Question::find($request->question_id);

      if ($request->case_study_id && $request->case_study_id !== $question->case_study_id) {
        $question['case_study_id'] = 'NONE' ? null : $request->case_study_id;
      }

      $question->update($qFields);
      
      // Upcoming question type is different from old one
      if ($request->type !== $question->type) {
        // Cleaning previous question items
        switch ($request->type) {
          case 'ESSAY':
            if ($question->type === 'MULTIPLE') {
              $question->choices()->delete();
            } else {
              $question->checklists()->delete();
            }
            break;
            
          case 'MULTIPLE':
            if ($question->type === 'CHECKLIST') {
              $question->checklists()->delete();
            }
            break;
          
          default:
            if ($question->type === 'MULTIPLE') { 
              $question->choices()->delete();
            }
            break;
        }
        
        // Create new question items
        if ($request->items) {
          $question->essay = null;
  
          foreach ($request->items as $item) {
            $iFields = (array) json_decode($item);
            $iFields['id'] = Str::uuid();
            
            $model = $request->type == 'MULTIPLE' ? Choice::class : Checklist::class;
    
            $item = $model::create($iFields);
            $item->question()->associate($question);
            $item->save();
          }
        }
      } else {
        // Upcoming question type is the same as the old one
        // Update question items with new data
        if ($request->items) {
          $items = $question->type === 'MULTIPLE' ? $question->choices() : $question->checklists();
          $order = $question->type === 'MULTIPLE' ? 'point' : 'number';
          
          $items = $items->orderBy($order, 'asc')->get();

          foreach ($items as $key => $item) {
            $iFields = (array) json_decode($request->items[$key]);

            $item->update($iFields);
          }
        }
      }
      
      $question->type = $request->type;
      $question->save();

      return res('Successfully updated question', $question, 201);
    };
    
    return catcher($fn);
  }

  public function deleteLevels (Request $request) {
    $fn = function () use ($request) {
      Level::destroy($request->level_id);

      return res('Successfully deleted level', null, 204);
    };

    return catcher($fn);
  }

  public function deleteCaseStudies (Request $request) {
    $fn = function () use ($request) {
      $caseStudy = CaseStudy::find($request->case_study_id);

      // Disassociate children, which in this case Questions
      $caseStudy->questions()->transform(function ($question) {
        $question->caseStudy()->disassociate();
        $question->save();

        return $question;
      });

      $caseStudy->delete();

      return res('Successfully deleted case study', null, 204);
    };
    
    return catcher($fn);
  }

  public function deleteQuestions (Request $request) {
    $fn = function () use ($request) {
      Question::destroy($request->question_id);

      return res('Successfully deleted question', null, 204);
    };
    
    return catcher($fn);
  }

  public function allQuestions (Request $request) {
    $fn = function () use ($request) {
      $questions = Level::find($request->level_id)
                        ->questions()
                        ->select('id', 'level_id', 'number', 'body', 'type', 'created_at', 'updated_at')
                        ->orderBy('number')
                        ->get();

      return res('Success.', $questions);
    };
    
    return catcher($fn);
  }

  public function showQuestions (Request $request) {
    $fn = function () use ($request) {
      $question = Question::find($request->question_id);

      if ($question->type !== 'ESSAY') {
        $opt = [
          'items' => $question->type === 'MULTIPLE' ? 'choices' : 'checklists',
          'order' => $question->type === 'MULTIPLE' ? 'point' : 'number'
        ];
        
        $question->load([$opt['items'] => function ($query) use ($opt) {
          $query->orderBy($opt['order']);
        }]);
      }

      return res('Success.', $question);
    };
    
    return catcher($fn);
  }

}
