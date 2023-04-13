<?php

namespace Argo\Domain\Listing\Mapper;

use App\Domain\Address\Mapper\AddressMapper;
use App\Domain\ListingModel\Mapper\ListingModelMapper;
use App\Domain\ListingTag\Mapper\ListingTagMapper;
use Linkedcode\DataMapper\Adapter\PdoAdapter;
use Linkedcode\DataMapper\Mapper\AbstractStdClassMapper;

/**
 * Autogenerado, no editar
 */
abstract class AbstractListingMapper extends AbstractStdClassMapper
{
    protected $table = 'listing';
    protected $primaryKey = 'id';

    protected $fields = [
        'id' => ['field' => 'id', 'type' => 'serial', 'required' => false],
        'title' => ['field' => 'title', 'type' => 'string', 'required' => false],
        'address' => ['field' => 'address_id', 'type' => 'entity', 'required' => false, 'entity' => 'Address'],
    ];

    protected AddressMapper $addressMapper;
    protected ListingTagMapper $listingTagMapper;
    protected ListingModelMapper $listingModelMapper;

    protected $relations = [
        'tags' => ['name' => 'tags', 'type' => 'M2M', 'mapper' => 'listingTagMapper', 'foreignKey' => false],
        'models' => ['name' => 'models', 'type' => 'O2M', 'mapper' => 'listingModelMapper', 'foreignKey' => false],
    ];

    public function __construct(
        PdoAdapter $adapter,
        AddressMapper $addressMapper,
        ListingTagMapper $listingTagMapper,
        ListingModelMapper $listingModelMapper,
    ) {
        $this->adapter = $adapter;
        $this->addressMapper = $addressMapper;
        $this->listingTagMapper = $listingTagMapper;
        $this->listingModelMapper = $listingModelMapper;
    }
}
