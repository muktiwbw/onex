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

      // Calculate non-essay answers
      // ...
    };
    
    return catcher($fn);
  }

  public function createReports (Request $request) {
      
    }

}
