<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswerSheet extends Model
{
  use HasFactory;
  
  public $incrementing = false;

  protected $fillable = [
    'id', 'user_id', 'level_id', 'isFinished' 
  ];

  public function user () {
    return $this->belongsTo(User::class);
  }

  public function level () {
    return $this->belongsTo(Level::class);
  }

  public function answers () {
    return $this->hasMany(Answer::class);
  }

  public function report () {
    return $this->hasOne(Report::class);
  }

  //======================================
  //====== METHODS =======================
  //======================================
  
  public function getMultipleChoiceAnswers () {
    return $this
          ->answers()
          ->whereNotNull('point')
          ->get()
          ->map(function ($answer) {
            $question = $answer->question;
            
            $correctAnswer = $question
                            ->choices()
                            ->where('isCorrect', true)
                            ->first();

            $submittedAnwerBody = $question
                                 ->choices()
                                 ->where('point', $answer->point)
                                 ->first()->body;

            return [
              'number' => $question->number,
              'body' => $question->body,
              'score' => $answer->point === $correctAnswer->point ? $question->score : 0,
              'submittedAnswer' => "{$answer->point}. {$submittedAnwerBody}",
              'correctAnswer' => "{$correctAnswer->point}. {$correctAnswer->body}"
            ];
          });
  }

  public function getChecklistAnswers () {
    return $this
          ->answers()
          ->whereNotNull('checklist')
          ->get()
          ->map(function ($answer) {
            $question = $answer->question;

            $checklistQuestionScore = $question->score;
            $checklistItemsCount = $question->checklists->count();
            $checklistScorePerItem = $checklistQuestionScore / $checklistItemsCount;
            
            $checklistItems = $question
                            ->checklists()
                            ->orderBy('number', 'asc')
                            ->get();

            $submittedChecklistItems = collect(json_decode($answer->checklist))
                                      ->map(function ($checklist, $key) use ($checklistItems, $checklistScorePerItem) {
                                        
                                        return [
                                          'number' => $checklist->number,
                                          'score' => $checklist->answer === $checklistItems[$key]->answer ? $checklistScorePerItem : 0,
                                          'body' => $checklistItems[$key]->body,
                                          'submittedAnswer' => $checklist->answer,
                                          'correctAnswer' => $checklistItems[$key]->answer
                                        ];
                                      });
            
            return [
              'number' => $question->number,
              'checklist' => $submittedChecklistItems,
              'score' => $submittedChecklistItems
                        ->map(function ($item) {
                          return $item['score'];
                        })->sum()
            ];
          });
  }

  public function getMultipleChoiceFinalScore () {
    return $this
          ->getMultipleChoiceAnswers()
          ->map(function ($choice) {
            return $choice['score'];
          })->sum();
  }

  public function getChecklistFinalScore () {
    return $this
          ->getChecklistAnswers()
          ->map(function ($checklist) {
            return $checklist['score'];
          })->sum();
  }

  public function getNonEssayAnswersFinalScore () {
    return $this->getMultipleChoiceFinalScore() + $this->getChecklistFinalScore();
  }

  public function getEssayAnswers () {
    return $this
          ->answers()
          ->whereNotNull('essay')
          ->get()
          ->map(function ($answer) {
            $question = $answer->question;

            return [
              'number' => $question->number,
              'body' => $question->body,
              'score' => $question->score,
              'submittedAnswer' => $answer->essay,
              'correctAnswer' => $question->essay
            ];
          })->sortBy('number')->values(); 

    // The function values() to reset array index.
    //  Apparently the sortBy() function returned an object
  } 

  public function getAssessedEssayAnswers ($assessments) {
    return $this
          ->getEssayAnswers()
          ->transform(function ($answer, $key) use ($assessments) {
            $answer['score'] = $assessments[$key]['score'];

            return $answer;
          });
  }

  public function getUnansweredQuestions ($type, $answereds) {
    $answeredNumbers = collect($answereds)
                      ->map(function ($answered) {
                        return $answered['number'];
                      });
    return $this
          ->level
          ->questions()
          ->where('type', $type)
          ->whereNotIn('number', $answeredNumbers)
          ->get()
          ->transform(function ($question) use ($type) {
            $question->score = 0;
            $question->submittedAnswer = null;

            switch ($type) {
              case 'MULTIPLE':
                $correctChoice = $question->choices()->where('isCorrect', true)->first();
                $question->correctAnswer = "{$correctChoice->point}. {$correctChoice->body}";
                break;
              
              case 'ESSAY':
                $question->correctAnswer = $question->essay;
                break;
            }

            return $question->only('number', 'body', 'score', 'submittedAnswer', 'correctAnswer');
          });
  }

  public function calculateExamFinalScore ($assessments) {
    // Calculate essay score
    $essayAnswersFinalScore = collect($assessments)
                              ->filter(function ($essay) {
                                return $essay['score'] > 0;
                              })->map(function ($essay) {
                                return $essay['score'];
                              })->sum();

    // Calculate and return exam final score
    return $this->getNonEssayAnswersFinalScore() + $essayAnswersFinalScore;
  }

  public function getExamReport ($assessments) {
    // Get answered questions
    $multipleChoiceAnswers = $this->getMultipleChoiceAnswers();
    $checklistAnswers = $this->getChecklistAnswers();
    $essayAnswers = $this->getAssessedEssayAnswers($assessments);

    // Get unanswered questions
    $unansweredMultipleChoice = $this->getUnansweredQuestions('MULTIPLE', $multipleChoiceAnswers);
    $unansweredChecklistAnswers = $this->getUnansweredQuestions('CHECKLIST', $checklistAnswers);
    $unansweredEssayAnswers = $this->getUnansweredQuestions('ESSAY', $essayAnswers);

    // Merge answered and unanswered questions together, sort by number
    $multipleChoiceAnswers = $multipleChoiceAnswers->merge($unansweredMultipleChoice)->sortBy('number')->values();
    $essayAnswers = $essayAnswers->merge($unansweredEssayAnswers)->sortBy('number')->values();

    return json_encode([
      'multipleChoice' => $multipleChoiceAnswers, 
      'essay' => $essayAnswers,
      'checklist' => $checklistAnswers 
    ]);
  }
}
