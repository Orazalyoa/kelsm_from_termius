<?php

namespace App\Admin\Repositories;

use App\Models\ChatParticipant as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class ChatParticipant extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}


