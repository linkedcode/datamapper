<?php

namespace Argo\Domain\Address\Mapper;

use App\Domain\Country\Mapper\CountryMapper;
use Linkedcode\DataMapper\Adapter\PdoAdapter;
use Linkedcode\DataMapper\Mapper\AbstractStdClassMapper;

/**
 * Autogenerado, no editar
 */
abstract class AbstractAddressMapper extends AbstractStdClassMapper
{
    protected $table = 'address';
    protected $primaryKey = 'id';

    protected $fields = [
        'id' => ['field' => 'id', 'type' => 'serial', 'required' => false],
        'title' => ['field' => 'title', 'type' => 'string', 'required' => false],
        'country' => ['field' => 'country_id', 'type' => 'entity', 'required' => false, 'entity' => 'Country'],
    ];

    protected CountryMapper $countryMapper;

    public function __construct(PdoAdapter $adapter, CountryMapper $countryMapper)
    {
        $this->adapter = $adapter;
        $this->countryMapper = $countryMapper;
    }
}
