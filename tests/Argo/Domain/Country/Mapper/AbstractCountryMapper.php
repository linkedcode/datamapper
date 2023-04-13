<?php

namespace Argo\Domain\Country\Mapper;

use Linkedcode\DataMapper\Mapper\AbstractStdClassMapper;

/**
 * Autogenerado, no editar
 */
abstract class AbstractCountryMapper extends AbstractStdClassMapper
{
    protected $table = 'country';
    protected $primaryKey = 'id';

    protected $fields = [
        'id' => ['field' => 'id', 'type' => 'serial', 'required' => false],
        'name' => ['field' => 'name', 'type' => 'string', 'required' => false],
    ];
}
