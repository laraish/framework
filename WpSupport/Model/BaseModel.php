<?php

namespace Laraish\WpSupport\Model;

abstract class BaseModel
{
    /**
     * The model's attributes(cached value).
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Get an attribute from the model.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttributeFromCache($key);
        }

        return $this->getAttributeFromMethod($key);
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string $key
     *
     * @return mixed
     */
    protected function getAttributeFromCache($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return null;
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string $key
     *
     * @return mixed
     */
    protected function getAttributeFromMethod($key)
    {
        if (method_exists($this, $key)) {
            return $this->$key();
        }

        return null;
    }

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Store arbitrary data to the $attribute array.
     *
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected function setAttribute($key, $value)
    {
        $namespaceMethod = explode('::', $key);
        $key             = isset($namespaceMethod[1]) ? $namespaceMethod[1] : $key;

        $this->attributes[$key] = $value;

        return $value;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

}