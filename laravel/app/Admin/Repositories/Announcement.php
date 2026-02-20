<?php

namespace App\Admin\Repositories;

use App\Models\Announcement as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Announcement extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}

