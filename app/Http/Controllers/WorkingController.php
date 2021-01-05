<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Models\Level;
use App\Models\Question;
use App\Models\AnswerSheet;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Checklist;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;

use Auth;

class WorkingController extends Controller
{
  public function createAnswerSheets (Request $request) {
    $fn = function () use ($request) {
      
      $answerSheet = Auth::user()->answerSheets()->where('level_id', $request->level_id)->first();

      if (!$answerSheet) {
        $answerSheet = AnswerSheet::create([
          'id' => Str::uuid(),
          'level_id' => $request->level_id,
          'user_id' => Auth::id()
        ]);
      }

      return res('Successfully created or getting an existing answer sheet.', $answerSheet, 200);
    };
    
    return catcher($fn);
  }

  public function allQuestions (Request $request) {
    // dd($request->all(), $level_id, $request->input('level_id'), $request->query('level_id'), $request->level_id);
    $fn = function () use ($request) {
      $questions = Level::find($request->level_id)
                        ->questions()
                        ->select('id', 'number', 'type')
                        ->orderBy('number', 'asc')
                        ->get();

      return res('Success.', $questions);
    };
    
    return catcher($fn);
  }

  public function showQuestions (Request $request) {
    $fn = function () use ($request) {
      // returns question data along with its id
      $question = Question::find($request->question_id);
      $answerSheet = AnswerSheet::find($request->answer_sheet_id);

      // load question with items
      if ($question->type !== 'ESSAY') {
        $question = $question->load($question->type === 'MULTIPLE' ? 'choices' : 'checklists');
      }

      // Load user's answer if any
      $answer = $answerSheet->answers()
                            ->where('question_id', $request->question_id)
                            ->first();

      $payload = $answer ? [$question, $answer] : $question;

      return res('Success.', $payload);
    };
    
    return catcher($fn);
    
  }

  public function submitAnswers (Request $request) {
    $fn = function () use ($request) {
      $answer = AnswerSheet::find($request->answer_sheet_id)
                           ->answers()
                           ->where('question_id', $request->question_id)
                           ->first();

      if (!$answer) {
        $answer = Answer::create([
          'id' => Str::uuid(),
          'answer_sheet_id' => $request->answer_sheet_id,
          'question_id' => $request->question_id
        ]);
      }

      $question = $answer->question;

      // Each case is checking whether if current answer is empty or different from new answer
      switch ($question->type) {
        case 'ESSAY':
          if (!$answer->essay || $answer->essay !== $request->answer) 
            $answer->essay = $request->answer;
          break;
        
        case 'MULTIPLE':
          if (!$answer->point || $answer->point !== $request->answer) 
            $answer->point = $request->answer;
          break;
        
        default:
          if (!$answer->checklist || $answer->checklist !== $request->answer) 
            $answer->checklist = $request->answer;
          break;
      }

      $answer->save();

      return res('Success submitting answer.', $answer, 201);
    };
    
    return catcher($fn);
  }

  public function finishAnswerSheets (Request $request) {
    $fn = function () use ($request) {
      $answerSheet = AnswerSheet::find($request->answer_sheet_id);
      $answerSheet->isFinished = true;
      $answerSheet->save();

      return res('Successfully finished answer sheet.', $answerSheet);
    };
    
    return catcher($fn);
  }
}
