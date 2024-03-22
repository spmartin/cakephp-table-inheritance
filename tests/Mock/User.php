<?php

namespace Spmartin\TableInheritance\Test\Mock;

use Cake\ORM\Entity;
use Spmartin\TableInheritance\Model\Entity\CopyableEntityInterface;
use Spmartin\TableInheritance\Model\Entity\CopyableEntityTrait;

class User extends Entity implements CopyableEntityInterface
{
    use CopyableEntityTrait;
}
