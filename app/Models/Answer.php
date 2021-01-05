<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
  use HasFactory;
  
  public $incrementing = false;

  protected $fillable = [
    'id', 'answer_sheet_id', 'question_id', 'point', 'essay', 'checklist'
  ];

  public function question () {
    return $this->belongsTo(Question::class);
  }

  public function answerSheet () {
    return $this->belongsTo(AnswerSheet::class);
  }
}
