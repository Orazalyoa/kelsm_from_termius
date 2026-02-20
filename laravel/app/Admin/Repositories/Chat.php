<?php

namespace App\Admin\Repositories;

use App\Models\Chat as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Chat extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}


