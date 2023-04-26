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

$adapter->exec("TRUNCATE TABLE listing");

$invalidData = array(
    "title" => "",
    "address_id" => 999,
    'extraField' => 'ignore'
);
try {
    $res = $listingMapper->validate($invalidData);
    var_dump($res);
    if ($res === false) {
        $err = $listingMapper->getErrors();
        print_r($err);
    }
} catch (Exception $e) {
    echo $e->getMessage();
}

die();


$post = array(
    "title" => "Titulo 1",
    "address_id" => 1
);
$listingMapper->create($post);

$listing = $listingMapper->findById(1);
print_r($listing);

$post = array(
    "title" => "Titulo 2",
    "address_id" => 2,
);
$listingMapper->create($post);

$post3 = array(
    "title" => "Titulo MAL"
);
$listingMapper->create($post3);
$listing = $listingMapper->findAll(array(
    'title' => "Titulo MAL"
));
print_r($listing);

$update = array(
    "title" => "Titulo correctamente editado"
);
$listingMapper->update($update, array('id' => 3));

$editado = $listingMapper->findById(3);
print_r($editado);