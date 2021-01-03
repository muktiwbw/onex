<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{
  use HasFactory;
  
  public $incrementing = false;

  protected $fillable = [
    'id', 'question_id', 'point', 'body', 'isCorrect'  
  ];

  public function question () {
    return $this->belongsTo(Question::class);
  }
}
