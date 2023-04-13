<?php

namespace App\Domain\ListingTag\Mapper;

use Argo\Domain\ListingTag\Mapper\AbstractListingTagMapper;

class ListingTagMapper extends AbstractListingTagMapper
{
    public function findRelated($ids)
    {
        $this->tagMapper->join($this);
        return $this->tagMapper->findAll(array(
            'listing_id' => $ids
        ));
    }

    public function getMainEntity()
    {
        return $this->fields['listing'];
    }
}