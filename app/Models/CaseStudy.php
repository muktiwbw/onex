<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
  use HasFactory;
  
  public $incrementing = false;

  protected $fillable = [
    'id', 'number', 'title', 'body', 'type'
  ];

  // public function level () {
  //   return $this->belongsTo(Level::class);
  // }

  public function questions () {
    return $this->hasMany(Question::class);
  }
}
