<?php

namespace App\Admin\Repositories;

use App\Models\AdvicesCategory as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class AdvicesCategory extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
    public $timestamps = false;

    static $status = [
        1 => "显示",
        0 => "隐藏"
    ];

}
