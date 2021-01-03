<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
  use HasFactory;
  
  public $incrementing = false;

  protected $fillable = [
    'id', 'level_id', 'case_study_id', 'number', 'body', 'type', 'essay', 'score'
  ];

  public function level () {
    return $this->belongsTo(Level::class);
  }

  public function case_study () {
    return $this->belongsTo(CaseStudy::class);
  }

  public function choices() {
    return $this->hasMany(Choice::class);
  }

  public function checklists() {
    return $this->hasMany(Checklist::class);
  }
}
