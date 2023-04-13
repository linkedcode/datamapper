<?php

namespace Argo\Domain\ListingModel\Mapper;

use Linkedcode\DataMapper\Mapper\AbstractStdClassMapper;

/**
 * Autogenerado, no editar
 */
abstract class AbstractListingModelMapper extends AbstractStdClassMapper
{
    protected $table = 'listing_model';
    protected $primaryKey = 'id';

    protected $fields = [
        'id' => ['field' => 'id', 'type' => 'serial', 'required' => false],
        'name' => ['field' => 'name', 'type' => 'string', 'required' => false],
        'color' => ['field' => 'color', 'type' => 'string', 'required' => false],
    ];
}
