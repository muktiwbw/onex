<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
  use HasFactory;
  
  public $incrementing = false;

  protected $fillable = [
    'id', 'question_id', 'number', 'body', 'answer'
  ];

  public function question () {
    return $this->belongsTo(Question::class);
  }
}
