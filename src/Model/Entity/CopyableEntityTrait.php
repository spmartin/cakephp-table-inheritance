<?php

namespace Spmartin\TableInheritance\Model\Entity;

trait CopyableEntityTrait
{

    /**
     * @return array
     */
    public function copyProperties(): array
    {
        return $this->_fields;
    }
}
