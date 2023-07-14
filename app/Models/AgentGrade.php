<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AgentGrade extends Model
{

    protected $table = 'agent_grade';
    protected $primaryKey = 'id';
    protected $guarded = [];

    public static function getCachedGradeOption()
    {
        return Cache::remember('agent_grade_option', 60, function () {
            return self::query()->orderBy('key','asc')->pluck('value','key')->toArray();
        });
    }

}
