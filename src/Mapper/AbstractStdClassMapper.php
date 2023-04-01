<?php

namespace Linkedcode\DataMapper\Mapper;

use Exception;
use Linkedcode\DataMapper\Adapter\DatabaseAdapterInterface;
use stdClass;

abstract class AbstractStdClassMapper
{
    protected $adapter;
    protected $entityTable;
    protected $fields = [];
    protected $relations = [];

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

    public function create(array $data)
    {
        $obj = new stdClass;

        $obj->id = $this->adapter->insert(
            $this->entityTable,
            $this->toRow($data)
        );

        return $obj;
    }

    public function update(array $data, array $conditions)
    {
        return $this->adapter->update(
            $this->entityTable,
            $this->toRow($data),
            $conditions
        );
    }

    protected function toRow($data)
    {
        $newdata = [];

        foreach ($data as $k => $v) {
            foreach ($this->fields as $field) {
                if ($field['field'] === $k) {
                    $newdata[$k] = $v;
                    continue;
                }
            }
        }

        return $newdata;
    }

    protected function loadRelatedEntities($rows)
    {
        $rows = $this->removeExtraColumns($rows);
        //$rows = $this->loadEnums($rows);
        $rows = $this->loadInternalEntities($rows);
        $rows = $this->loadRelations($rows);
        return $rows;
    }

    protected function removeExtraColumns(array $rows)
    {
        if (empty($rows)) {
            return $rows;
        }

        $columnsToDelete = [];
        $columnsInModel = $this->getColumns();

        foreach ($rows[0] as $prop => $value) {
            if (!in_array($prop, $columnsInModel)) {
                $columnsToDelete[] = $prop;
            }
        }

        foreach ($rows as &$row) {
            foreach ($columnsToDelete as $col) {
                unset($row->{$col});
            }
        }

        return $rows;
    }

    protected function loadRelations($rows)
    {
        if (empty($this->relations)) {
            return $rows;
        }

        foreach ($this->relations as $attribute => $relation) {
            foreach ($rows as &$row) {
                if ($relation['type'] == 'XX') {
                    $field = $relation['localKey'];
                    $attribute = $relation['field'];
                    $this->{$relation['mapper']}->join(
                        'INNER',
                        $relation['joinTable'],
                        $relation['localKey'],
                        $relation['foreignKey']
                    );
                    $this->{$relation['mapper']}->addForeignKey($relation['localKey']);
                    $row->$attribute = $this->{$relation['mapper']}->findAll(array(
                        $field => $row->id
                    ));
                } else if ($relation['type'] == 'O2M' || $relation['type'] == '1X') {
                    $field = $relation['foreignKey'];
                    $row->$attribute = $this->{$relation['mapper']}->findAll(array(
                        $field => $row->id
                    ));
                } else {
                    throw new Exception("Falta programar relacion");
                }
            }
        }

        return $rows;
    }

    protected function loadInternalEntities($rows)
    {
        $related = [];
        $columnsToRemove = [];

        foreach ($this->fields as $prop => $field) {
            /*if (in_array($prop, $this->excludes)) {
                $columnsToRemove[] = $field['field'];
                continue;
            }*/

            if ($field['type'] == 'entity') {
                $mapper = lcfirst($field['entity']) . "Mapper";
                if (!isset($this->$mapper)) {
                    continue;
                }

                $ids = $this->getRelatedIds($rows, $field['field']);

                if (!empty($ids)) {
                    $related[$prop] = $this->$mapper->findByIds($ids);
                }
            }
        }

        if (!empty($related)) {
            foreach ($rows as &$row) {
                foreach ($related as $prop => $values) {
                    $column = $this->getColumn($prop);
                    $id = $row->{$column};
                    if (isset($values[$id])) {
                        $row->{$prop} = $values[$id];
                    } else {
                        $row->{$prop} = null;
                    }
                    if ($column != $prop) {
                        unset($row->{$column});
                    }
                }
            }
        }

        if (count($columnsToRemove)) {
            foreach ($rows as &$row) {
                foreach ($columnsToRemove as $col) {
                    unset($row->$col);
                }
            }
        }

        return $rows;
    }

    public function getColumn($attr)
    {
        if (isset($this->fields[$attr])) {
            if (isset($this->fields[$attr]['column'])) {
                return $this->fields[$attr]['column'];
            } else if (isset($this->fields[$attr]['field'])) {
                return $this->fields[$attr]['field'];
            } else {
                return $attr;
            }
        } else {
            // ej. user_id
            foreach ($this->fields as $field) {
                if ($field['field'] == $attr) {
                    return $attr;
                }
            }
        }

        return false;
    }

    protected function getRelatedIds($rows, $field)
    {
        $ids = [];

        foreach ($rows as $row) {
            if (!empty($row->{$field})) {
                $ids[] = $row->{$field};
            }
        }

        return array_unique($ids);
    }

    protected function getColumns()
    {
        $columnsInModel = [];

        foreach ($this->fields as $field) {
            $columnsInModel[] = $field['field'];
        }

        return $columnsInModel;
    }
}