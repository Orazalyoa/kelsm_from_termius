<?php

namespace App\Admin\Repositories;

use App\Models\Consultation as ConsultationModel;
use Dcat\Admin\Repositories\EloquentRepository;

class Consultation extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = ConsultationModel::class;
}

