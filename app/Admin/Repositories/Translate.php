<?php

namespace App\Admin\Repositories;

use App\Models\Translate as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Translate extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
