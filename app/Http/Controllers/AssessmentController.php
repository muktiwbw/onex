<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Level;
use App\Models\AnswerSheet;

use Auth;

class AssessmentController extends Controller
{
  public function allAnswerSheets (Request $request) {
    $fn = function () use ($request) {
      $answerSheets = Level::find($request->level_id)
                            ->answerSheets()
                            ->select('id', 'updated_at as finished_at','user_id')
                            ->where('isFinished', true)
                            ->orderBy('updated_at', 'desc')
                            ->with('user:id,name')
                            ->get();

      return res('Success.', $answerSheets);
    };
    
    return catcher($fn);
  }

  public function showAnswerSheets (Request $request) {
    $fn = function () use ($request) {
      $answerSheet = AnswerSheet::find($request->answer_sheet_id);

      // Calculate multiple choice answers
      $multipleChoiceAnswers = $answerSheet
                              ->answers()
                              ->whereNotNull('point')
                              ->get()
                              ->map(function ($answer) {
                                $correctPoint = $answer->question
                                                       ->choices()
                                                       ->where('isCorrect', true)
                                                       ->first()->point;
                                return [
                                  'number' => $answer->question->number,
                                  'score' => $answer->point === $correctPoint ? $answer->question->score : 0,
                                  'submittedAnswer' => $answer->point,
                                  'correctAnswer' => $correctPoint
                                ];
                              });
      
      $multipleChoiceFinalScore = $multipleChoiceAnswers
                                  ->map(function ($choice) {
                                    return $choice['score'];
                                  })->sum();

      // Calculate checklist answers
      $checklistAnswers = $answerSheet
                         ->answers()
                         ->whereNotNull('checklist')
                         ->get()
                         ->map(function ($answer) {
                           $checklistQuestionScore = $answer->question->score;
                           $checklistItemsCount = $answer->question->checklists->count();
                           $checklistScorePerItem = $checklistQuestionScore / $checklistItemsCount;
                           
                           $checklistItems = $answer->question
                                                    ->checklists()
                                                    ->orderBy('number', 'asc')
                                                    ->get();

                           $submittedChecklistItems = collect(json_decode($answer->checklist))
                                                      ->map(function ($checklist, $key) use ($checklistItems, $checklistScorePerItem) {
                                                        
                                                        return [
                                                          'number' => $checklist->number,
                                                          'score' => $checklist->answer === $checklistItems[$key]->answer ? $checklistScorePerItem : 0,
                                                          'submittedAnswer' => $checklist->answer,
                                                          'correctAnswer' => $checklistItems[$key]->answer
                                                        ];
                                                      });
                                                      
                           return [
                             'number' => $answer->question->number,
                             'checklist' => json_encode($submittedChecklistItems),
                             'score' => $submittedChecklistItems
                                        ->map(function ($item) {
                                          return $item['score'];
                                        })->sum()
                           ];
                         });

      // dd($checklistAnswers[0]);        
    };
    
    return catcher($fn);
  }

  public function createReports (Request $request) {
      
    }

}
