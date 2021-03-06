<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Entity;

class CommonEntity
{
    /**
     * @var array
     */
    protected $changes = [];

    /**
     * Wrapper function for isProperty methods.
     *
     * @param string $name
     * @param        $arguments
     *
     * @throws \InvalidArgumentException
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'is') === 0 && method_exists($this, 'get'.ucfirst($name))) {
            return $this->{'get'.ucfirst($name)}();
        } elseif ($name == 'getName' && method_exists($this, 'getTitle')) {
            return $this->getTitle();
        }

        throw new \InvalidArgumentException('Method '.$name.' not exists');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $string = get_called_class();
        if (method_exists($this, 'getId')) {
            $string .= ' with ID #'.$this->getId();
        }

        return $string;
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->addChange($prop, [$currentId, $newId]);
            }
        } elseif ($current != $val) {
            $this->addChange($prop, [$current, $val]);
        }
    }

    /**
     * @param $key
     * @param $value
     */
    protected function addChange($key, $value)
    {
        if (isset($this->changes[$key]) && is_array($this->changes[$key])) {
            $this->changes[$key] = array_merge($this->changes[$key], $value);
        } else {
            $this->changes[$key] = $value;
        }
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Reset changes.
     */
    public function resetChanges()
    {
        $this->changes = [];
    }
}
