<?php

namespace App\Admin\Repositories;

use App\Models\Message as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Message extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    /**
     * 预加载关联
     */
    protected function getQuery()
    {
        return $this->newQuery()->with(['chat', 'sender', 'statuses.user']);
    }
}


