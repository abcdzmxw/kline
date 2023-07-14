<?php

namespace App\Admin\Repositories;

use App\Models\Admin\AdminSetting as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class AdminSetting extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
