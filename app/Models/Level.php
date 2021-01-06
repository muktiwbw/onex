<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
  use HasFactory;
  
  public $incrementing = false;

  protected $fillable = [
    'id', 'name', 'tujuan', 'uraian', 'examThreshold', 'evaluationThreshold'
  ];

  public function questions () {
    return $this->hasMany(Question::class);
  }

  public function answerSheets () {
    return $this->hasMany(AnswerSheet::class);
  }

  //======================================
  //====== METHODS =======================
  //======================================

  public function getFinishedAnswerSheetsSnippet () {
    return $this
          ->answerSheets()
          ->select('id', 'updated_at as finished_at','user_id')
          ->where('isFinished', true)
          ->orderBy('updated_at', 'desc')
          ->with('user:id,name')
          ->get();
  }
}
