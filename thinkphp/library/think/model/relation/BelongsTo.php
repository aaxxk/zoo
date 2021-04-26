<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\model\relation;

use think\db\Query;
use think\Loader;
use think\Model;

class BelongsTo extends OneToOne
{
    /**
     * Constructor
     * @access public
     * @param Model  $parent Superior model object
     * @param string $model Model name
     * @param string $foreignKey Associative foreign key
     * @param string $localKey Associated primary key
     * @param string $joinType JOIN type
     * @param string $relation  Association name
     */
    public function __construct(Model $parent, $model, $foreignKey, $localKey, $joinType = 'INNER', $relation = null)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->joinType   = $joinType;
        $this->query      = (new $model)->db();
        $this->relation   = $relation;
    }

    /**
     * Delay in obtaining associated data
     * @param string   $subRelation Child association name
     * @param \Closure $closure     Closure query conditions
     * @access public
     * @return array|false|\PDOStatement|string|Model
     */
    public function getRelation($subRelation = '', $closure = null)
    {
        $foreignKey = $this->foreignKey;
        if ($closure) {
            call_user_func_array($closure, [ & $this->query]);
        }
        $relationModel = $this->query
            ->removeWhereField($this->localKey)
            ->where($this->localKey, $this->parent->$foreignKey)
            ->relation($subRelation)
            ->find();

        if ($relationModel) {
            $relationModel->setParent(clone $this->parent);
        }

        return $relationModel;
    }

    /**
     * Query the current model based on the associated conditions
     * @access public
     * @param string  $operator Comparison operator
     * @param integer $count    Number
     * @param string  $id       Statistics field of the related table
     * @return Query
     */
    public function has($operator = '>=', $count = 1, $id = '*')
    {
        return $this->parent;
    }

    /**
     * Query the current model based on the associated conditions
     * @access public
     * @param  mixed  $where Query conditions (array or closure)
     * @param  mixed  $fields   Field
     * @return Query
     */
    public function hasWhere($where = [], $fields = null)
    {
        $table    = $this->query->getTable();
        $model    = basename(str_replace('\\', '/', get_class($this->parent)));
        $relation = basename(str_replace('\\', '/', $this->model));

        if (is_array($where)) {
            foreach ($where as $key => $val) {
                if (false === strpos($key, '.')) {
                    $where[$relation . '.' . $key] = $val;
                    unset($where[$key]);
                }
            }
        }
        $fields = $this->getRelationQueryFields($fields, $model);

        return $this->parent->db()->alias($model)
            ->field($fields)
            ->join([$table => $relation], $model . '.' . $this->foreignKey . '=' . $relation . '.' . $this->localKey, $this->joinType)
            ->where($where);
    }

    /**
     * Preload related queries (data sets)
     * @access public
     * @param array     $resultSet data set
     * @param string    $relation Current association name
     * @param string    $subRelation Child association name
     * @param \Closure  $closure Closure
     * @return void
     */
    protected function eagerlySet(&$resultSet, $relation, $subRelation, $closure)
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        $range = [];
        foreach ($resultSet as $result) {
            // Get a list of associated foreign keys
            if (isset($result->$foreignKey)) {
                $range[] = $result->$foreignKey;
            }
        }

        if (!empty($range)) {
            $this->query->removeWhereField($localKey);
            $data = $this->eagerlyWhere($this->query, [
                $localKey => [
                    'in',
                    $range,
                ],
            ], $localKey, $relation, $subRelation, $closure);
            // Associated attribute name
            $attr = Loader::parseName($relation);
            // Linked Data Encapsulation
            foreach ($resultSet as $result) {
                // Association model
                if (!isset($data[$result->$foreignKey])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$foreignKey];
                    $relationModel->setParent(clone $result);
                    $relationModel->isUpdate(true);
                }

                if (!empty($this->bindAttr)) {
                    // Binding associated attributes
                    $this->bindAttr($relationModel, $result, $this->bindAttr);
                } else {
                    // Set association properties
                    $result->setRelation($attr, $relationModel);
                }
            }
        }
    }

    /**
     * Preload related queries (data)
     * @access public
     * @param Model     $result Data object
     * @param string    $relation Current association name
     * @param string    $subRelation Child association name
     * @param \Closure  $closure Closure
     * @return void
     */
    protected function eagerlyOne(&$result, $relation, $subRelation, $closure)
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;
        $this->query->removeWhereField($localKey);
        $data = $this->eagerlyWhere($this->query, [$localKey => $result->$foreignKey], $localKey, $relation, $subRelation, $closure);
        // Association model
        if (!isset($data[$result->$foreignKey])) {
            $relationModel = null;
        } else {
            $relationModel = $data[$result->$foreignKey];
            $relationModel->setParent(clone $result);
            $relationModel->isUpdate(true);
        }
        if (!empty($this->bindAttr)) {
            // Binding associated attributes
            $this->bindAttr($relationModel, $result, $this->bindAttr);
        } else {
            // Set association properties
            $result->setRelation(Loader::parseName($relation), $relationModel);
        }
    }

    /**
     * Add associated data
     * @access public
     * @param Model $model       Associated model objects
     * @return Model
     */
    public function associate($model)
    {
        $foreignKey = $this->foreignKey;
        $pk         = $model->getPk();

        $this->parent->setAttr($foreignKey, $model->$pk);
        $this->parent->save();

        return $this->parent->setRelation($this->relation, $model);
    }

    /**
     * Unregister associated data
     * @access public
     * @return Model
     */
    public function dissociate()
    {
        $foreignKey = $this->foreignKey;

        $this->parent->setAttr($foreignKey, null);
        $this->parent->save();

        return $this->parent->setRelation($this->relation, null);
    }

    /**
     * Perform basic query (execute only once)
     * @access protected
     * @return void
     */
    protected function baseQuery()
    {
        if (empty($this->baseQuery)) {
            if (isset($this->parent->{$this->foreignKey})) {
                // Related query brings in related conditions
                $this->query->where($this->localKey, '=', $this->parent->{$this->foreignKey});
            }

            $this->baseQuery = true;
        }
    }
}
