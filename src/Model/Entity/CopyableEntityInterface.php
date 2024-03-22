<?php

namespace Spmartin\TableInheritance\Model\Entity;

use Cake\Datasource\EntityInterface;

interface CopyableEntityInterface extends EntityInterface
{
    /**
     * @return array
     */
    public function copyProperties(): array;
}
