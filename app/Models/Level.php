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

    public function caseStudies () {
        return $this->hasMany(CaseStudy::class);
    }

    public function questions () {
        return $this->hasMany(Question::class);
    }
}
