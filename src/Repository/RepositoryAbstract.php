<?php

namespace Linkedcode\DataMapper\Repository;

use Linkedcode\DataMapper\Interface\MapperInterface;

abstract class RepositoryAbstract
{
    /**
     * @var Mapper
     */
    protected $mapper;

    public function __construct(MapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    public function create(array $data)
    {
        return $this->mapper->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->mapper->update($id, $data);
    }

    public function raw($id)
    {
        return $this->mapper->findRawById($id);
    }

    public function findById($id)
    {
        return $this->mapper->findById($id);
    }

    public function removeById($id)
    {
        return $this->mapper->removeById($id);
    }

    public function findAll(array $params = [])
    {
        return $this->mapper->findAll($params);
    }

    public function find(array $params = [])
    {
        return $this->mapper->find($params);
    }
}