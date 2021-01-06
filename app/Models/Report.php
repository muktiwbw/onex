<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
  use HasFactory;
  
  public $incrementing = false;

  protected $fillable = [
    'id', 'answer_sheet_id', 'examData', 'score'
  ];

  public function answerSheet () {
    return $this->belongsTo(AnswerSheet::class);
  }
}
