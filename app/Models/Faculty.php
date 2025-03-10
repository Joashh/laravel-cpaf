<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = [
        "first_name",
        "research_id",
        "last_name",
        "middle_name",
        "fullname",
        "employee_category",
        "employment_status",
        "unit",
        "ms_phd",
        "designation",
        "fulltime_partime",
    ];

    public function internationalPublicationAwards(){
        return $this->hasMany(InternationalPublicationAwards::class);
    }
    public function otherNotableAwards(){
        return $this->hasMany(OtherNotableAwards::class);
    }
    public function journalArticles(){
        return $this->hasMany(journalArticle::class);
    }

    public function paperPresented(){
        return $this->hasMany(paperPresented::class);
    }

    public function chapterBook(){
        return $this->hasMany(chapterInBook::class);
    }

    public function research(){
       return $this->hasMany(research::class);
    }

    public function trainingAttended(){
        return $this->hasMany(TrainingAttended::class);
    }

    public function fsrorrsr(){
        return $this->hasMany(FSRorRSR::class);
    }

}
