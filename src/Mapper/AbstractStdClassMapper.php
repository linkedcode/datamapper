<?php

namespace Linkedcode\DataMapper\Mapper;

use Linkedcode\DataMapper\Adapter\DatabaseAdapterInterface;

abstract class AbstractDataMapper
{
    protected $adapter;
    protected $entityTable;

    public function __construct(DatabaseAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function findById($id)
    {
        $this->adapter->select(
            $this->entityTable,
            array('id' => $id)
        );

        if (!$row = $this->adapter->fetch()) {
            return null;
        }

        return $row;
    }

    public function findAll(array $conditions = array())
    {
        $this->adapter->select($this->entityTable, $conditions);
        $rows = $this->adapter->fetchAll();
        return $rows;
    }
}
