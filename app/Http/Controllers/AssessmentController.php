<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Level;
use App\Models\AnswerSheet;

use Auth;

class AssessmentController extends Controller
{
  public function allAnswerSheets (Request $request) {
    $fn = function () use ($request) {
      // Get finished answer sheets
      $answerSheets = Level::find($request->level_id)->getFinishedAnswerSheetsSnippet();

      // Send to client
      return res('Success.', $answerSheets);
    };
    
    return catcher($fn);
  }

  public function showAnswerSheets (Request $request) {
    $fn = function () use ($request) {
      $answerSheet = AnswerSheet::find($request->answer_sheet_id);

      // Get non-essay answers' score
      $nonEssayAnswersFinalScore = $answerSheet->getNonEssayAnswersFinalScore();

      // Get essay answers
      $essayAnswers = $answerSheet->getEssayAnswers();
      
      // Send to client
      $payload = [
        'nonEssayAnswersFinalScore' => $nonEssayAnswersFinalScore,
        'essayAnswers' => $essayAnswers
      ];

      return res('Success.', $payload);
    };
    
    return catcher($fn);
  }

  public function submitReports (Request $request) {
    $fn = function () use ($request) {                              
      $answerSheet = AnswerSheet::find($request->answer_sheet_id);

      // Calculate exam final score
      $examFinalScore = $answerSheet->calculateExamFinalScore($request->assessments);

      // Get exam report
      $examReport = $answerSheet->getExamReport($request->assessments);

      // Submit report to db
      $report = $answerSheet->report()->create([
        'id' => Str::uuid(),
        'examData' => $examReport,
        'score' => $examFinalScore
      ]);

      return res('Successfully submitted exam report.', $report->id);
    };
    
    return catcher($fn);
  }

}
