<?php

namespace App\Admin\Repositories;

use App\Models\ChatFile as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class ChatFile extends EloquentRepository
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
        return $this->newQuery()->with(['chat', 'message', 'uploader']);
    }
}


