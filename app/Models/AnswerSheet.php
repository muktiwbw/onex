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
}
