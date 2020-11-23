<?php

namespace Laraish\Support\Wp\Model;

use JsonSerializable;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

abstract class BaseModel implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * The model's attributes(cached value).
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The ACF fields
     * @var array
     */
    protected $acfFields;

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * Get an attribute from the model.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->getAttributeFromCache($key);
        }

        if (method_exists($this, $key)) {
            return $this->getAttributeFromMethod($key);
        }

        if ($originalMethod = $this->getOriginalKey($key)) {
            if (method_exists($this, $originalMethod)) {
                return strip_tags($this->$originalMethod);
            }
        }

        if ($this->getAcfFields()) {
            return $this->getAttributeFromAcfFields($key);
        }

        return null;
    }

    /**
     * Get the ACF fields.
     * @return array
     */
    public function getAcfFields(): array
    {
        return $this->acfFields ?? ($this->acfFields = $this->resolveAcfFields() ?: []);
    }

    /**
     * Resolve the ACF fields.
     * @return mixed
     */
    public function resolveAcfFields()
    {
        return [];
    }

    /**
     * Remove the modifier `text` from the given method name.
     * e.g. `permalink_text` --> `permalink`
     *
     * @param string $key
     * @return string
     */
    protected function getOriginalKey(string $key): ?string
    {
        $originalKey = preg_replace('/_text$/', '', $key);

        return $originalKey === $key ? null : $originalKey;
    }

    /**
     * Get an attribute from the $attributes array.
     *
     * @param  string $key
     * @return mixed
     */
    protected function getAttributeFromCache(string $key)
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
     * @return mixed
     */
    protected function getAttributeFromMethod(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->$key();
        }

        return null;
    }

    /**
     * Get the attribute from ACF fields.
     *
     * @param string $key
     * @return mixed|null|string
     */
    protected function getAttributeFromAcfFields(string $key)
    {
        if (array_key_exists($key, (array) $this->acfFields)) {
            return $this->acfFields[$key];
        }

        if ($originalKey = $this->getOriginalKey($key)) {
            if (array_key_exists($originalKey, (array) $this->acfFields)) {
                return strip_tags($this->acfFields[$originalKey]);
            }
        }

        return null;
    }

    /**
     * Get all of the current attributes on the model.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set all of the current attributes on the model.
     *
     * @param array $attributes
     * @return static
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Store arbitrary data to the $attribute array.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute(string $key, $value)
    {
        $namespaceMethod = explode('::', $key);
        $key = $namespaceMethod[1] ?? $key;

        $this->attributes[$key] = $value;

        return $value;
    }

    /**
     * Convert model to array.
     * @return array
     */
    public function toArray(): array
    {
        $attributes = (new Collection($this->visible))->mapWithKeys(function ($attribute) {
            return [$attribute => $this->{$attribute}];
        });

        return $attributes->all();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);

        return $json;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
}
