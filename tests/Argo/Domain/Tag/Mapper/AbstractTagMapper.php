<?php

namespace Argo\Domain\Tag\Mapper;

use Linkedcode\DataMapper\Mapper\AbstractStdClassMapper;

/**
 * Autogenerado, no editar
 */
abstract class AbstractTagMapper extends AbstractStdClassMapper
{
    protected $table = 'tag';
    protected $primaryKey = 'id';

    protected $fields = [
        'id' => ['field' => 'id', 'type' => 'serial', 'required' => false],
        'name' => ['field' => 'name', 'type' => 'string', 'required' => false],
    ];
}
