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
use think\Exception;
use think\Loader;
use think\Model;
use think\model\Relation;

class MorphOne extends Relation
{
    // Polymorphic field
    protected $morphKey;
    protected $morphType;
    // Polymorphic type
    protected $type;

    /**
     * Constructor
     * @access public
     * @param Model  $parent    Superior model object
     * @param string $model     Model name
     * @param string $morphKey  Associative foreign key
     * @param string $morphType Polymorphic field name
     * @param string $type      Polymorphic type
     */
    public function __construct(Model $parent, $model, $morphKey, $morphType, $type)
    {
        $this->parent    = $parent;
        $this->model     = $model;
        $this->type      = $type;
        $this->morphKey  = $morphKey;
        $this->morphType = $morphType;
        $this->query     = (new $model)->db();
    }

    /**
     * Delay in obtaining associated data
     * @param string   $subRelation Child association name
     * @param \Closure $closure     Closure query conditions
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getRelation($subRelation = '', $closure = null)
    {
        if ($closure) {
            call_user_func_array($closure, [ & $this->query]);
        }
        $relationModel = $this->relation($subRelation)->find();

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
     * @param string  $joinType JOIN type
     * @return Query
     */
    public function has($operator = '>=', $count = 1, $id = '*', $joinType = 'INNER')
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
        throw new Exception('relation not support: hasWhere');
    }

    /**
     * Preload related queries
     * @access public
     * @param array    $resultSet   data set
     * @param string   $relation    Current association name
     * @param string   $subRelation Child association name
     * @param \Closure $closure     Closure
     * @return void
     */
    public function eagerlyResultSet(&$resultSet, $relation, $subRelation, $closure)
    {
        $morphType = $this->morphType;
        $morphKey  = $this->morphKey;
        $type      = $this->type;
        $range     = [];
        foreach ($resultSet as $result) {
            $pk = $result->getPk();
            // Get a list of associated foreign keys
            if (isset($result->$pk)) {
                $range[] = $result->$pk;
            }
        }

        if (!empty($range)) {
            $data = $this->eagerlyMorphToOne([
                $morphKey  => ['in', $range],
                $morphType => $type,
            ], $relation, $subRelation, $closure);
            // Associated attribute name
            $attr = Loader::parseName($relation);
            // Linked Data Encapsulation
            foreach ($resultSet as $result) {
                if (!isset($data[$result->$pk])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$pk];
                    $relationModel->setParent(clone $result);
                    $relationModel->isUpdate(true);
                }

                $result->setRelation($attr, $relationModel);
            }
        }
    }

    /**
     * Preload related queries
     * @access public
     * @param Model    $result      Data object
     * @param string   $relation    Current association name
     * @param string   $subRelation Child association name
     * @param \Closure $closure     Closure
     * @return void
     */
    public function eagerlyResult(&$result, $relation, $subRelation, $closure)
    {
        $pk = $result->getPk();
        if (isset($result->$pk)) {
            $pk   = $result->$pk;
            $data = $this->eagerlyMorphToOne([
                $this->morphKey  => $pk,
                $this->morphType => $this->type,
            ], $relation, $subRelation, $closure);

            if (isset($data[$pk])) {
                $relationModel = $data[$pk];
                $relationModel->setParent(clone $result);
                $relationModel->isUpdate(true);
            } else {
                $relationModel = null;
            }

            $result->setRelation(Loader::parseName($relation), $relationModel);
        }
    }

    /**
     * Polymorphic one-to-one association model pre-query
     * @access   public
     * @param array         $where       Associate pre-query conditions
     * @param string        $relation    Association name
     * @param string        $subRelation Subassociation
     * @param bool|\Closure $closure     Closure
     * @return array
     */
    protected function eagerlyMorphToOne($where, $relation, $subRelation = '', $closure = false)
    {
        // Pre-loading related queries Support nested pre-loading
        if ($closure) {
            call_user_func_array($closure, [ & $this]);
        }
        $list     = $this->query->where($where)->with($subRelation)->find();
        $morphKey = $this->morphKey;
        // Assemble model data
        $data = [];
        foreach ($list as $set) {
            $data[$set->$morphKey][] = $set;
        }
        return $data;
    }

    /**
     * Save (new) current associated data object
     * @access public
     * @param mixed $data Data can use the primary key of an array, associative model object and associative object
     * @return Model|false
     */
    public function save($data)
    {
        if ($data instanceof Model) {
            $data = $data->getData();
        }
        // Save related table data
        $pk = $this->parent->getPk();

        $model                  = new $this->model;
        $data[$this->morphKey]  = $this->parent->$pk;
        $data[$this->morphType] = $this->type;
        return $model->save($data) ? $model : false;
    }

    /**
     * Perform basic query (execute once)
     * @access protected
     * @return void
     */
    protected function baseQuery()
    {
        if (empty($this->baseQuery) && $this->parent->getData()) {
            $pk                    = $this->parent->getPk();
            $map[$this->morphKey]  = $this->parent->$pk;
            $map[$this->morphType] = $this->type;
            $this->query->where($map);
            $this->baseQuery = true;
        }
    }

}
