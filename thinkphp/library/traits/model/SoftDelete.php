<?php

namespace traits\model;

use think\db\Query;
use think\Model;

/**
 * @mixin \Think\Model
 */
trait SoftDelete
{
    /**
     * Determine whether the current instance has been soft deleted
     * @access public
     * @return boolean
     */
    public function trashed()
    {
        $field = $this->getDeleteTimeField();

        if ($field && !empty($this->data[$field])) {
            return true;
        }
        return false;
    }

    /**
     * Query data that contains soft deleted
     * @access public
     * @return Query
     */
    public static function withTrashed()
    {
        return (new static )->getQuery();
    }

    /**
     * Only query soft deleted data
     * @access public
     * @return Query
     */
    public static function onlyTrashed()
    {
        $model = new static();
        $field = $model->getDeleteTimeField(true);

        if ($field) {
            return $model->getQuery()->useSoftDelete($field, ['not null', '']);
        } else {
            return $model->getQuery();
        }
    }

    /**
     * Delete the current record
     * @access public
     * @param bool $force Whether to force deletion
     * @return integer
     */
    public function delete($force = false)
    {
        if (false === $this->trigger('before_delete', $this)) {
            return false;
        }

        $name = $this->getDeleteTimeField();
        if ($name && !$force) {
            // Soft delete
            $this->data[$name] = $this->autoWriteTimestamp($name);
            $result            = $this->isUpdate()->save();
        } else {
            // Force deletion of current model data
            $result = $this->getQuery()->where($this->getWhere())->delete();
        }

        // Association delete
        if (!empty($this->relationWrite)) {
            foreach ($this->relationWrite as $key => $name) {
                $name   = is_numeric($key) ? $name : $key;
                $result = $this->getRelation($name);
                if ($result instanceof Model) {
                    $result->delete();
                } elseif ($result instanceof Collection || is_array($result)) {
                    foreach ($result as $model) {
                        $model->delete();
                    }
                }
            }
        }

        $this->trigger('after_delete', $this);

        // Clear the original data
        $this->origin = [];

        return $result;
    }

    /**
     * Delete Record
     * @access public
     * @param mixed $data  Primary key list (supports closure query conditions)
     * @param bool  $force Whether to force deletion
     * @return integer Number of records successfully deleted
     */
    public static function destroy($data, $force = false)
    {
        if (is_null($data)) {
            return 0;
        }

        // Contains soft deleted data
        $query = self::withTrashed();
        if (is_array($data) && key($data) !== 0) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [ & $query]);
            $data = null;
        }

        $count = 0;
        if ($resultSet = $query->select($data)) {
            foreach ($resultSet as $data) {
                $result = $data->delete($force);
                $count += $result;
            }
        }

        return $count;
    }

    /**
     * Recover soft deleted records
     * @access public
     * @param array $where Update conditions
     * @return integer
     */
    public function restore($where = [])
    {
        if (empty($where)) {
            $pk         = $this->getPk();
            $where[$pk] = $this->getData($pk);
        }

        $name = $this->getDeleteTimeField();

        if ($name) {
            // Undelete
            return $this->getQuery()
                ->useSoftDelete($name, ['not null', ''])
                ->where($where)
                ->update([$name => null]);
        } else {
            return 0;
        }
    }

    /**
     * The query does not contain soft deleted data by default
     * @access protected
     * @param Query $query Query object
     * @return Query
     */
    protected function base($query)
    {
        $field = $this->getDeleteTimeField(true);
        return $field ? $query->useSoftDelete($field) : $query;
    }

    /**
     * Get soft deleted fields
     * @access public
     * @param bool $read Whether to query operation (the table alias will be automatically removed when writing)
     * @return string
     */
    protected function getDeleteTimeField($read = false)
    {
        $field = property_exists($this, 'deleteTime') && isset($this->deleteTime) ?
        $this->deleteTime :
        'delete_time';

        if (false === $field) {
            return false;
        }

        if (!strpos($field, '.')) {
            $field = '__TABLE__.' . $field;
        }

        if (!$read && strpos($field, '.')) {
            $array = explode('.', $field);
            $field = array_pop($array);
        }

        return $field;
    }
}
