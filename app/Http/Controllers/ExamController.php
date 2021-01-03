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
        $caseStudy->leve_id = $request->level_id;
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

      $level = Level::find($request->level_id);
      $lastQuestionAdded = $level->questions()->orderBy('number', 'desc')->first();
      $qFields['number'] = !$lastQuestionAdded ? 1 : $lastQuestionAdded->number + 1;

      $question = Question::create($qFields);
      $question->level()->associate($level);
      
      if ($request->case_study_id) {
        $question->case_study_id = $request->case_study_id;
      }

      $question->save();

      if ($question->type !== 'ESSAY') {
        // Question Items (Multiple Choice or Checklist)
        array_map(function ($items) use ($question) {
          $iFields = (array) json_decode($items);
          $iFields['id'] = Str::uuid();
          
          $model = $question->type == 'MULTIPLE' ? Choice::class : Checklist::class;
  
          $item = $model::create($iFields);
          $item->question()->associate($question);
          $item->save();
        }, $request->items);
      }
      
      return res('Successfully created question', $question, 201);
    };
    
    return catcher($fn);
  }

  public function updateLevels ($id, Request $request) {
    $fn = function () use ($id, $request) {
      $fields = $request->only('name', 'tujuan', 'uraian', 'examThreshold', 'evaluationThreshold');

      $level = Level::find($id);
      
      $level->update($fields);

      return res('Successfully updated level', $level, 201);
    };

    return catcher($fn);
  }
  
  public function updateCaseStudies ($id, Request $request) {
    $fn = function () use ($id, $request) {
      $fields = $request->only('number', 'title', 'body', 'type');

      $caseStudy = CaseStudy::find($id);

      $caseStudy->update($fields);

      if ($request->level_id && $request->level_id != $caseStudy->level_id) {
        
        $caseStudy->level_id = $request->level_id;
        $caseStudy->save();
      }

      return res('Successfully updated case study', $caseStudy, 201);
    };
    
    return catcher($fn);
  }

  public function updateQuestions ($id, Request $request) {
    $fn = function () use ($id, $request) {
      // Question
      $qFields = $request->only('body', 'essay', 'score');
      $question = Question::find($id);

      if ($request->case_study_id !== $question->case_study_id) {
        $question['case_study_id'] = 'NONE' ? null : $request->case_study_id;
      }

      $question->update($qFields);
      
      // Question Items
      if ($request->type !== $question->type) {
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

        $question->type = $request->type;
        $question->save();

        if ($request->type !== 'ESSAY') {
          $question->essay = null;
          $question->save();

          array_map(function ($items) use ($question) {
            $iFields = (array) json_decode($items);
            $iFields['id'] = Str::uuid();
            
            $model = $question->type == 'MULTIPLE' ? Choice::class : Checklist::class;
    
            $item = $model::create($iFields);
            $item->question()->associate($question);
            $item->save();
          }, $request->items);
        }
      }

      return res('Successfully updated question', $question, 201);
    };
    
    return catcher($fn);
  }

  public function deleteLevels ($id) {
    $fn = function () use ($id) {
      Level::destroy($id);

      return res('Successfully deleted level', null, 204);
    };

    return catcher($fn);
  }

  public function deleteCaseStudies ($id) {
    $fn = function () use ($id) {
      $caseStudy = CaseStudy::find($id);

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

  public function deleteQuestions ($id) {
    $fn = function () use ($id) {
      Question::destroy($id);

      return res('Successfully deleted question', null, 204);
    };
    
    return catcher($fn);
  }

}
