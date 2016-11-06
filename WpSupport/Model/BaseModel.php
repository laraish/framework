<?php

namespace Laraish\WpSupport\Model;

abstract class BaseModel
{
    protected static $data_pool = [];

    /**
     * Get object's property on demand
     *
     * @param $property
     *
     * @return mixed
     */
    public function __get($property)
    {
        return $this->$property = $this->$property();
    }


    /**
     * Try to get data from data-pool associated with the $key
     * If there is no data to get then return null
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected static function cache($key, $value)
    {
        $data = static::get_pool_data($key);

        return $data ? $data : static::set_pool_data($key, $value);
    }

    /**
     * Try to get the data from data-pool
     * if there is no data to get then return null
     *
     * @param null $key
     *
     * @return array|null
     */
    protected static function get_pool_data($key = null)
    {
        // if $key is not supplied return the whole data set
        if ($key === null) {
            return static::$data_pool;
        }

        $data_in_pool = null;

        if (array_key_exists($key, static::$data_pool)) {
            $data_in_pool = static::$data_pool[$key];
        }

        return $data_in_pool;
    }

    /**
     * Store arbitrary data to the data-pool
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected static function set_pool_data($key, $value)
    {
        static::$data_pool[$key] = $value;

        return $value;
    }

}