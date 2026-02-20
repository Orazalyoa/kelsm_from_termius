<?php

namespace App\Admin\Repositories;

use App\Models\InviteCode as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class InviteCode extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
