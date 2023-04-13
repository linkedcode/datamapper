<?php

use App\Domain\Address\Mapper\AddressMapper;
use App\Domain\Country\Mapper\CountryMapper;
use App\Domain\Listing\Mapper\ListingMapper;
use App\Domain\ListingModel\Mapper\ListingModelMapper;
use App\Domain\ListingTag\Mapper\ListingTagMapper;
use App\Domain\Tag\Mapper\TagMapper;
use Linkedcode\DataMapper\Adapter\PdoAdapter;

require_once "../vendor/autoload.php";

$adapter = new PdoAdapter("mysql:host=localhost;dbname=datamapper", "kosciuk", "a");
$countryMapper = new CountryMapper($adapter);
$addressMapper = new AddressMapper($adapter, $countryMapper);
$tagMapper = new TagMapper($adapter);
$listingTagMapper = new ListingTagMapper($adapter, $tagMapper);
$listingModelMapper = new ListingModelMapper($adapter);
$listingMapper = new ListingMapper($adapter, $addressMapper, $listingTagMapper, $listingModelMapper);

$listing = $listingMapper->findById(1);
print_r($listing);