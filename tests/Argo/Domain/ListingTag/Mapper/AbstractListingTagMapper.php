<?php

namespace Argo\Domain\ListingTag\Mapper;

use App\Domain\Tag\Mapper\TagMapper;
use Linkedcode\DataMapper\Adapter\PdoAdapter;
use Linkedcode\DataMapper\Mapper\AbstractStdClassMapper;

/**
 * Autogenerado, no editar
 */
abstract class AbstractListingTagMapper extends AbstractStdClassMapper
{
    protected $table = 'listing_tag';
    protected $primaryKey = [];

    protected $fields = [
        'listing' => ['field' => 'listing_id', 'type' => 'integer', 'required' => false],
        'tag' => ['field' => 'tag_id', 'type' => 'entity', 'required' => false, 'entity' => 'Tag'],
    ];

    protected TagMapper $tagMapper;

    public function __construct(PdoAdapter $adapter, TagMapper $tagMapper)
    {
        $this->adapter = $adapter;
        $this->tagMapper = $tagMapper;
    }
}
