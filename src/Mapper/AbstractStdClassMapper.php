<?php

namespace Linkedcode\DataMapper\Mapper;

use Exception;
use Linkedcode\DataMapper\Adapter\DatabaseAdapterInterface;
use Linkedcode\DataMapper\Adapter\PdoAdapter;
use stdClass;

abstract class AbstractStdClassMapper
{
    /**
     * @var PdoAdapter
     */
    protected $adapter;
    protected $table;
    protected $fields = [];
    protected $relations = [];
    protected $errors = [];

    public function __construct(DatabaseAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function findById($id)
    {
        $this->adapter->select(
            $this->table,
            array('id' => $id)
        );

        if (!$row = $this->adapter->fetch()) {
            return null;
        }

        $row = $this->loadRelatedEntities(array($row));

        return $row[0];
    }

    public function findRawById($id)
    {
        $this->adapter->select(
            $this->table,
            array('id' => $id)
        );

        if (!$row = $this->adapter->fetch()) {
            return null;
        }

        return $row;
    }

    public function findByIds(array $ids)
    {
        $this->adapter->select($this->table, ['id' => $ids]);

        $rows = $this->adapter->fetchAll();

        if ($rows) {
            $rows = $this->loadRelatedEntities($rows);
        }

        return $rows;
    }

    protected function getTable()
    {
        return $this->table;
    }

    protected function getEntities()
    {
        $entities = [];

        foreach ($this->fields as $field) {
            if ($field['type'] == 'entity') {
                $entities[] = $field;
            }
        }

        return $entities;
    }

    public function join(AbstractStdClassMapper $mapper)
    {
        $local = $this->getEntities();
        $foreign = $mapper->getEntities();

        if (count($foreign) === 0) {
            $entity = $this->getEntity($mapper);
            foreach ($local as $_local) {
                if ($_local['entity'] == $entity) {
                    $on = sprintf("%s.id = %s.%s",
                        $mapper->getTable(),
                        $this->getTable(),
                        $_local['field']
                    );
                    $this->adapter->join($mapper->getTable(), $on);
                }
            }
        } else if (count($local) === 0) {
            $entity = $this->getEntity($this);
            foreach ($foreign as $_foreign) {
                if ($_foreign['entity'] == $entity) {
                    $on = sprintf("%s.%s = %s.id",
                        $mapper->getTable(),
                        $_foreign['field'],
                        $this->getTable()
                    );
                    $this->adapter->join($mapper->getTable(), $on);
                }
            }
        } else {
            foreach ($local as $_local) {
                foreach ($foreign as $_foreign) {
                    if ($_local['entity'] == $_foreign['entity']) {
                        print_r($_local);
                        die("join");
                    }
                }
            }
        }
    }

    public function findAll(array $conditions = array())
    {
        $this->adapter->select($this->table, $conditions);
        $rows = $this->adapter->fetchAll();
        $this->adapter->reset();
        return $rows;
    }

    public function create(array $data)
    {
        $obj = new stdClass;

        $obj->id = $this->adapter->insert(
            $this->table,
            $this->toRow($data)
        );

        return $obj;
    }

    public function update(array $data, array $conditions)
    {
        return $this->adapter->update(
            $this->table,
            $this->toRow($data),
            $conditions
        );
    }

    protected function toRow($data)
    {
        $new = [];
        $fields = $this->fields;

        foreach ($data as $k => $v) {
            foreach ($fields as $f => $field) {
                if ($field['field'] === $k) {
                    $new[$k] = $v;
                    unset($fields[$f]);
                    continue;
                }
            }
        }

        return $new;
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

        $columnsInModel = $this->getColumns();
        $columnsInTable = array_keys((array) $rows[0]);
        $columnsToDelete = array_diff($columnsInTable, $columnsInModel);

        foreach ($rows as &$row) {
            foreach ($columnsToDelete as $col) {
                unset($row->{$col});
            }
        }

        return $rows;
    }

    protected function getEntity($mapper)
    {
        $className = explode('\\', get_class($mapper));
        $entity = str_replace("Mapper", "", array_pop($className));
        return $entity;
    }

    protected function loadRelations($rows)
    {
        if (empty($this->relations)) {
            return $rows;
        }

        foreach ($this->relations as $attribute => $relation) {
            if ($relation['type'] == 'XX' || $relation['type'] == 'M2M') {
                $ids = $this->getRelatedIds($rows, 'id');

                $mapper = $relation['mapper'];
                $items = $this->$mapper->findRelated($ids);
                $main = $this->$mapper->getMainEntity();
                $mainField = $main['field'];

                foreach ($rows as &$row) {
                    foreach ($items as $i => $item) {
                        if ($item->$mainField == $row->id) {
                            $row->$attribute[] = $item;
                            unset($items[$i]);
                        }
                    }
                }
            } else if ($relation['type'] == 'O2M' || $relation['type'] == '1X') {
                $mapper = $relation['mapper'];
                $mainField = $this->getTable() . "_id";
                $ids = $this->getRelatedIds($rows, 'id');
                $relateds = $this->$mapper->findAll(array(
                    $mainField => $ids
                ));
                foreach ($rows as &$row) {
                    foreach ($relateds as $r => $related) {
                        if ($row->id == $related->$mainField) {
                            unset($related->$mainField);
                            $row->$attribute[] = $related;
                            unset($relateds[$r]);
                            continue;
                        }
                    }
                }
            } else {
                throw new Exception("Falta programar relacion");
            }
        }

        return $rows;
    }

    protected function loadInternalEntities($rows)
    {
        $related = [];
        //$columnsToRemove = [];

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
                    $value = null;
                    foreach ($values as $val) {
                        if ($val->id == $id) {
                            $value = $val;
                            break;
                        }
                    }
                    $row->{$prop} = $value;
                    if ($column != $prop) {
                        unset($row->{$column});
                    }
                }
            }
        }

        /*if (count($columnsToRemove)) {
            foreach ($rows as &$row) {
                foreach ($columnsToRemove as $col) {
                    unset($row->$col);
                }
            }
        }*/

        return $rows;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function validate($data)
    {
        $notFound = [];

        $columns = $this->getColumns();

        foreach ($data as $k => $v) {
            if (!in_array($k, $columns)) {
                $notFound[] = $k;
                continue;
            }

            $this->validateField($k, $data[$k]);
        }

        return empty($this->errors);
    }

    protected function checkRequired($field, $value)
    {
        if (isset($field['required']) && $field['required'] === true) {
            // ¿Que pasa con el 0?
            if ($value === "") {
                $this->errors[$field['name']]['required'] = false;
            }
        }
    }

    protected function checkEntities($field, $value)
    {
        if ($field['type'] === 'entity') {
            $mapper = sprintf("%sMapper", lcfirst($field['entity']));
            $related = $this->$mapper->findById($value);
            if (!$related) {
                $this->errors[$field['name']]['relatedEntity'] = false;
            }
        }
    }

    protected function validateField($fieldName, $value)
    {
        $field = $this->getAttribute($fieldName);
        $field['name'] = $fieldName;

        $this->checkRequired($field, $value);
        $this->checkEntities($field, $value);
    }

    protected function getAttribute($attr)
    {
        if (isset($this->fields[$attr])) {
            return $this->fields[$attr];
        } else {
            foreach ($this->fields as $field) {
                if ($field['field'] == $attr) {
                    return $field;
                }
            }
        }

        return false;
    }

    protected function getColumn($attr)
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
        return array_column($this->fields, 'field');
    }
}