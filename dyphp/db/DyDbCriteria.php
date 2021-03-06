<?php
/**
 * 数据查询
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
class DyDbCriteria{
    private $select = '*';
    private $distinct = false;
    private $from = '';
    private $join = ''; 
    private $where = '';
    private $group = '';
    private $having = '';
    private $order = '';
    private $offset = '';
    private $limit = '';

    public function __construct(){
        return $this;
    }

    /**
     * @brief    查询字段
     * @param    $select
     * @param    $isDistinct
     * @return   
     **/
    public function select($select='*',$isDistinct=false){
        $this->distinct = is_bool($isDistinct) ? $isDistinct : false;

        $select = trim($select);
        $select = empty($select) ? '*' : $select;
        if($select != '*'){
            $selectArr = explode(',',$select);
            $selectNewArr = array();
            foreach($selectArr as $key=>$val){
                $val = trim($val);
                if($val){
                    $selectNewArr[] = strpos($val,'.') !==false && stripos($val,' as ') !==false ? '`'.$val.'`' : $val;
                }
            }
            $select = implode(',',$selectNewArr);
            $select = empty($select) ? '*' : $select;
        }
        $this->select = $select;
        return $this;
    }

    /**
     * @brief    from条件
     * @param    $from   多个表时该参数为数组
     * @param    $alias  $from为数组时 该参数无效
     * @return   
     **/
    public function from($from,$alias=null){
        if(is_array($from)){
            $froms = '';
            foreach($from as $key=>$val){
                $froms .= is_int($key) ? $val.',' : $key. ' AS '.$val.',';
            }
            $this->from = substr($froms,0,-1);
        }elseif(is_string($from)){
            $this->from = $alias ? $from.' AS '.$alias : $from;
        }
        return $this;
    }

    /**
     * @brief    查询条件
     * @param    $key
     * @param    $val
     * @param    $condition
     * @param    $xor
     * @param    $compatible
     * @return   
     **/
    public function where($key,$val,$condition='=',$xor='AND'){
        $compatible = $this->join == '' ? true : false;
        $this->where .= $this->_where($key,$val,$condition,$xor,$compatible);
        return $this;
    }

    /**
     * @brief    group by
     * @param    $by
     * @return   
     **/
    public function group($by){
        if(is_string($by)){
            $this->group .= $this->group == '' ? " {$by} " : " ,{$by} ";
        }elseif(is_array($by)){
            $by = implode(',',$by);
            $this->group .= $this->group == '' ? " {$by} " : " ,{$by} ";
        }
        return $this;
    }

    /**
     * @brief    having
     * @param    $key
     * @param    $val
     * @param    $condition
     * @param    $xor
     * @param    $compatible
     * @return   
     **/
    public function having($key,$val,$condition='=',$xor='AND'){
        $compatible = $this->join == '' ? true : false;
        $this->having .= $this->_where($key,$val,$condition,$xor,$compatible);
        return $this;
    }

    /**
     * @brief    order by
     * @param    $by
     * @param    $order
     * @return   
     **/
    public function order($by,$order='ASC'){
        $order = in_array(strtoupper(trim($order)), array('ASC', 'DESC')) ? $order : ' ASC';
        $this->order .= $this->order == '' ? " {$by} {$order} " : ", {$by} {$order} ";
        return $this;
    }

    /**
     * @brief    offset
     * @param    $offset
     * @return   
     **/
    public function offset($offset=0){
        $this->offset = is_numeric($offset) && $offset > 0 ? $offset : 0;
        return $this;
    }

    /**
     * @brief    limit
     * @param    $limit
     * @return   
     **/
    public function limit($limit=1){
        $this->limit = is_numeric($limit) && $limit > 0 ? $limit : 1;
        return $this;
    }

    /**
     * @brief    join条件
     * @param    $join
     * @param    $on
     * @param    $type
     * @return   
     **/
    public function join($join='',$on='',$type='LEFT'){
        if(empty($join) || empty($on) || empty($type)){
            return false;
        }
        $type = strtoupper(trim($type));
        if (in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER','FULL'))){
            $this->join .= "{$type} JOIN {$join} ON {$on}";
        }
        return $this;
    }

    /**
     * @brief    获取sql项信息
     * @param    $item
     * @return   
     **/
    public function getSqlItem($item=''){
        return isset($this->$item) ? $this->$item : NULL;
    }

    /**
     * @brief    清除已设置的sql项 即 设置为默认值
     * @param    $item
     * @return   
     **/
    public function clearSqlItem($item=''){
        if(isset($this->$item)){
            if($item == 'distinct'){
                $this->distinct = false;
            }elseif($item == 'select'){
                $this->distinct = '*';
            }else{
                $this->$item = '';
            }
        }
    }

    /**
     * @brief    条件处理
     * @param    $key
     * @param    $val
     * @param    $condition    = > < >= <= <> in notin link llike rlike notlink notllike notrlike null notnull
     * @param    $xor
     * @param    $compatible  兼容性处理 
     * @return   
     **/
    private function _where($key,$val,$condition='=',$xor='AND',$compatible=true){
        $key = $compatible && trim($key) != '' ? "`{$key}`" : $key;
        $val = $compatible && is_string($val) ? "'{$val}'" : $val;

        $xor = strtoupper(trim($xor));
        $xor = $xor=='AND' ? 'AND' : 'OR';
        $xor = $this->where != '' ? $xor : '';

        switch($condition){
        case 'in':
            $where = " {$xor} {$key} IN({$val}) ";
            break;
        case 'notin':
            $where = " {$xor} {$key} NOT IN({$val}) ";
            break;
        case 'like':
            $where = " {$xor} {$key} LIKE '%$val%' ";
            break;
        case 'llike':
            $where = " {$xor} {$key} LIKE '%$val' ";
            break;
        case 'rlike':
            $where = " {$xor} {$key} LIKE '$val%' ";
            break;
        case 'notlike':
            $where = " {$xor} {$key} NOT LIKE '%$val%' ";
            break;
        case 'notllike':
            $where = " {$xor} {$key} NOT LIKE '%$val' ";
            break;
        case 'notrlike':
            $where = " {$xor} {$key} NOT LIKE '$val%' ";
            break;
        case 'null':
            $where = " {$xor} {$key} IS NULL ";
            break;
        case 'notnull':
            $where = " {$xor} {$key} IS NOT NULL ";
            break;
        default:
            $where = " {$xor} {$key} {$condition} {$val} ";
            break;
        }
        return $where;
    }

    /**
     * @brief    获取mysql sql语句
     * @param    $from  table name 
     * @return   
     **/
    public function getMysqlSql($from){
        $select = $this->distinct === true ? 'DISTINCT '.$this->select : $this->select;
        $from = $this->from != '' ? $this->from : '`'.$from.'`';
        $join = $this->join != '' ? $this->join : '';
        $where = 'WHERE '.($this->where !='' ? $this->where : '1=1');
        $group = $this->group !='' ? 'GROUP BY '.$this->group : '';
        $having = $this->having !='' ? 'HAVING '.$this->having : '';
        $order = $this->order !='' ? 'ORDER BY '.$this->order : '';
        $offset = intval($this->offset) >0 ? $this->offset.',' : '0,';
        $limit = intval($this->limit) >0 ? 'LIMIT '.$offset.$this->limit : '';
        $query = "SELECT {$select} FROM {$from} {$join} {$where} {$group} {$having} {$order} {$limit}";
        return $query;
    }

}

