<?php
/**
 * The file contains class Container()
 */
namespace Katran;

/**
 * Global storage class
 *
 * @package Application
 */
class Container
{
    /**
     * Storage var
     * @var array
     */
    private $storage = [];

    /**
     * Function return value
     *
     * @param   string    $name
     * @return  mixed
     */
    public function get($name = '', $default = null)
    {
        return isset($this->storage[$name])?$this->storage[$name]:$default;
    }


    /**
     * Function set value into storage
     *
     * @param   string    $name
     * @param   mixed     $value
     * @return  void
     */
    public function set($name = '', $value = null)
    {
        $this->storage[$name] = $value;
    }
}