<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\ServiceFeesRepository;
use App\Entities\ServiceFees;
use App\Validators\ServiceFeesValidator;

/**
 * Class ServiceFeesRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class ServiceFeesRepositoryEloquent extends BaseRepository implements ServiceFeesRepository
{
    /**
     * @var array
    */
    protected $fieldSearchable = [
        'service_fees'
    ];
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return ServiceFees::class;
    }

    

    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
    
}
