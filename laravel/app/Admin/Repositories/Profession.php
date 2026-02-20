<?php

namespace App\Admin\Repositories;

use App\Models\Profession as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Profession extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
