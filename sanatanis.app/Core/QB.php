<?php

namespace Core;

use Core\Model;

/**
 * QB Core
 */

class QB
{
    /* variable define for build query */
    public $sql;
    static $table_name;
    public $column_name;
    public $column_value;
    public $where;
    public $array;
    public $response;
    public $error;
    public $success = 0;
    public $orderby;
    public $limit;
    public $data;
    public $joinsql;
    public $groupby;
    /* variable define for build query */
    /* @method mixed table($params)
     * return tablename
     */
    public static function table($params)
    {
        static::$table_name = $params;
        return new static;
    }

    /* @method mixed where($params)
     * return where condition for sql
     */
    public function where($params)
    {
        $this->where = "";
        if (empty($params)) {
            $this->error[] = "Provide where condition";
        } else {
            foreach ($params as $key => $value) {
                if (count($value) == 1) {
                    $this->where .= " " . $value[0];
                } elseif (count($value) == 3) {
                    $sub = explode(".", $value[0]);
                    if (count($sub) == 1) {
                        $permitted_chars = 'abcdefghijklmnopqrstuvwxyz';
                        $wherevariable = substr(str_shuffle($permitted_chars), 0, 5);
                        $this->where .= " " . $value[0] . " " . $value[1] . " :" . $value[0] . $wherevariable;
                        $this->array[':' . $value[0] . $wherevariable] = $value[2];
                    } else {

                        $endname = end($sub);
                        $endname = rtrim($endname, ')');
                        $permitted_chars = 'abcdefghijklmnopqrstuvwxyz';
                        $wherevariable = substr(str_shuffle($permitted_chars), 0, 5);
                        $this->where .= " " . $value[0] . " " . $value[1] . " :" . $endname . $wherevariable;
                        $this->array[':' . $endname . $wherevariable] = $value[2];
                    }
                } elseif (count($value) > 3) {
                    if (strtolower($value[1]) == "between" or strtolower($value[1]) == "not between") {
                        $permitted_chars = 'abcdefghijklmnopqrstuvwxyz';
                        $btnamea = substr(str_shuffle($permitted_chars), 0, 5);
                        $btnameb = substr(str_shuffle($permitted_chars), 0, 5);
                        $this->where .= " " . $value[0] . " " . $value[1] . " CAST(:" . $btnamea . " AS SIGNED) " . $value[3] . " CAST(:" . $btnameb . " AS SIGNED) ";
                        $this->array[':' . $btnamea] = $value[2];
                        $this->array[':' . $btnameb] = $value[4];
                    }
                }
            }
        }
        return $this;
    }
    public function join(...$params)
    {
        // "SELECT t1.bname FROM hodocity_experience t1 INNER JOIN hodocity_business t2 ON t1.bid = t2.bid INNER JOIN hodocity_users t3 ON t2.uid = t3.uid WHERE t3.uid = :uid";
        if (empty($params)) {
            $this->error[] = "Provide where condition";
        } else {
            foreach ($params as $params_key => $params_value) {
                if ($params_key == 0) {
                    $this->joinsql .= " INNER JOIN " . $params_value . " ON";
                } else {
                    $this->joinsql .= " " . $params_value;
                }
            }
        }
        return $this;
    }
    public function leftjoin(...$params)
    {
        // "SELECT t1.bname FROM hodocity_experience t1 INNER JOIN hodocity_business t2 ON t1.bid = t2.bid INNER JOIN hodocity_users t3 ON t2.uid = t3.uid WHERE t3.uid = :uid";
        if (empty($params)) {
            $this->error[] = "Provide where condition";
        } else {
            foreach ($params as $params_key => $params_value) {
                if ($params_key == 0) {
                    $this->joinsql .= " LEFT JOIN " . $params_value . " ON";
                } else {
                    $this->joinsql .= " " . $params_value;
                }
            }
        }
        return $this;
    }
    public function rightjoin(...$params)
    {
        // "SELECT t1.bname FROM hodocity_experience t1 INNER JOIN hodocity_business t2 ON t1.bid = t2.bid INNER JOIN hodocity_users t3 ON t2.uid = t3.uid WHERE t3.uid = :uid";
        if (empty($params)) {
            $this->error[] = "Provide where condition";
        } else {
            foreach ($params as $params_key => $params_value) {
                if ($params_key == 0) {
                    $this->joinsql .= " RIGHT JOIN " . $params_value . " ON";
                } else {
                    $this->joinsql .= " " . $params_value;
                }
            }
        }
        return $this;
    }
    /* @method mixed delete() // delete data from table
     * return boolean
     */
    public function delete()
    {
        if (empty($this->error)) {
            if (empty(static::$table_name)) {
                // if table name empty
                $this->error[] = "Empty table name";
            } else {
                if (empty($this->where)) {
                    // if where closure empty
                    // build query
                    $this->sql = "DELETE FROM " . static::$table_name;
                } else {
                    // build query
                    $this->sql = "DELETE FROM " . static::$table_name . ' WHERE ' . $this->where;
                }
                // execute query
                $this->response = Model::execute($this->sql, $this->array);
                if ($this->response === true) {
                    $this->success = 1;
                    $this->error = "";
                } else {
                    $this->success = 0;
                    $this->error[] = $this->response;
                }
            }
        }
        $this->unset();
        return $this;
    }
    /* @method mixed insert() // insert data into table
     * return boolean
     */
    public function insert(...$params)
    {
        if (empty(static::$table_name)) {
            // if table name empty
            $this->error[] = "Empty table name";
        } else {
            foreach ($params as $params_key => $params_value) {
                $this->column_name = "";
                $this->column_value = "";
                $this->array = array();
                foreach ($params_value as $key => $value) {
                    $this->column_name .= " " . $key . ",";
                    $this->column_value .= " :" . $key . ",";
                    $this->array[':' . $key] = $value;
                }
                $this->column_name = rtrim($this->column_name, ",");
                $this->column_value = rtrim($this->column_value, ",");
                // build query
                $this->sql = "INSERT INTO " . static::$table_name . "(" . $this->column_name . ")" . " VALUES(" . $this->column_value . ")";
                // execute query
                $this->response = Model::execute($this->sql, $this->array);
                if ($this->response === true) {
                    $this->success += 1;
                    $this->error = "";
                } else {
                    $this->error[] = $this->response;
                }
            }
        }
        $this->unset();
        return $this;
    }
    public function insertgetid(...$params)
    {
        if (empty(static::$table_name)) {
            $this->error[] = "Undefined table name";
        } else {
            foreach ($params as $params_key => $params_value) {
                $this->column_name = "";
                $this->column_value = "";
                $this->array = array();
                foreach ($params_value as $key => $value) {
                    $this->column_name .= " " . $key . ",";
                    $this->column_value .= " :" . $key . ",";
                    $this->array[':' . $key] = $value;
                }
                $this->column_name = rtrim($this->column_name, ",");
                $this->column_value = rtrim($this->column_value, ",");

                $this->sql = "INSERT INTO " . static::$table_name . "(" . $this->column_name . ")" . " VALUES(" . $this->column_value . ")";
                $this->response = Model::insertgetid($this->sql, $this->array);
                if ($this->response != false) {
                    $this->data = $this->response;
                    $this->success = 1;
                    $this->error = "";
                } else {
                    $this->error[] = $this->response;
                }
            }
        }
        $this->unset();
        return $this;
    }
    /* @method mixed update() // update data into table
     * return boolean
     */
    public function update(...$params)
    {
        if (empty(static::$table_name)) {
            $this->error[] = "Empty table name";
        } else {

            foreach ($params as $params_key => $params_value) {
                $this->column_name = "";
                foreach ($params_value as $key => $value) {
                    $this->column_name .= " " . $key . " = " . ":$key" . ",";
                    $this->array[":" . $key] = $value;
                }
                $this->column_name = rtrim($this->column_name, ",");
                if (empty($this->where)) {
                    // build query
                    $this->sql = "UPDATE " . static::$table_name . " SET" . $this->column_name;
                } else {
                    // build query
                    $this->sql = "UPDATE " . static::$table_name . " SET" . $this->column_name . " WHERE " . $this->where . $this->orderby . $this->limit;
                }

                $this->response = Model::execute($this->sql, $this->array);
                if ($this->response === true) {
                    $this->success = 1;
                    $this->error = "";
                } else {
                    $this->error[] = $this->response;
                }
            }
        }
        $this->unset();
        return $this;
    }
    /* @method mixed select() // create column for select query
     * return column name
     */
    public function select(...$params)
    {
        if (empty(static::$table_name)) {
            $this->error[] = "Empty table name";
        } else {
            $this->sql = "";
            $this->column_name = "";
            foreach ($params as $params_key => $params_value) {
                $this->column_name .= " " . $params_value . ",";
            }
            $this->column_name = rtrim($this->column_name, ",");
        }
        return $this;
    }
    /* @method mixed groupby($params)
     * return groupby condition for sql
     */
    public function groupby(...$params)
    {
        if (!empty($params)) {
            $this->groupby = "GROUP BY";
            foreach ($params as $key => $value) {
                $this->groupby .= " " . $value;
            }
        }
        return $this;
    }
    /* @method mixed orderby() // create orderby condition
     * return orderby condition
     */
    public function orderby(...$params)
    {
        $this->orderby = " ORDER BY";
        foreach ($params as $params_key => $params_value) {
            $this->orderby .= " " . $params_value;
        }
        return $this;
    }
    /* @method mixed limit() //
     * return limit condition
     */
    public function limit($params)
    {
        $this->limit = " LIMIT " . $params;
        return $this;
    }
    /* @method mixed get() // create sql for select query and execute query
     * return data according to $params ()
     */
    public function get(...$params)
    {
        if (empty($this->joinsql)) {
            if (empty($this->where)) {
                $this->sql = "SELECT " . $this->column_name . " FROM " . static::$table_name . " " . $this->orderby . " " . $this->groupby . " " . $this->limit;
            } else {
                $this->sql = "SELECT " . $this->column_name . " FROM " . static::$table_name . " WHERE " . $this->where . " " . $this->groupby . " " . $this->orderby . " " . $this->limit;
            }
        } else {
            if (empty($this->where)) {
                $this->sql = "SELECT " . $this->column_name . " FROM " . static::$table_name . " " . $this->joinsql . " " . $this->orderby . " " . $this->groupby . " " . $this->limit;
            } else {
                $this->sql = "SELECT " . $this->column_name . " FROM " . static::$table_name . " " . $this->joinsql . " WHERE " . $this->where . " " . $this->groupby . " " . $this->orderby . " " . $this->limit;
            }
        }
        // "SELECT t1.bname FROM hodocity_experience t1 INNER JOIN hodocity_business t2 ON t1.bid = t2.bid INNER JOIN hodocity_users t3 ON t2.uid = t3.uid WHERE t3.uid = :t3.uid";

        $this->response = Model::executeSelect($this->sql, $this->array, $params);
        if ($this->response != false) {
            $this->success = 1;
            $this->error = "";
            $this->data = $this->response;
        } else {
            $this->error[] = "Empty Data";
        }

        $this->unset();
        return $this;
    }
    public function unset()
    {
        unset($this->sql);
        unset($this->column_name);
        unset($this->column_value);
        unset($this->where);
        unset($this->array);
        unset($this->response);
        unset($this->orderby);
        unset($this->limit);
        unset($this->joinsql);
        unset($this->groupby);
    }
}
