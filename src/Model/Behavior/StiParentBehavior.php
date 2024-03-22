<?php

namespace Spmartin\TableInheritance\Model\Behavior;

use ArrayAccess;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Spmartin\TableInheritance\Model\Entity\CopyableEntityInterface;

class StiParentBehavior extends Behavior
{

    use LocatorAwareTrait;
    use MatchesTrait;

    /**
     * Default options.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'tableMap' => [],
        'discriminatorMap' => [],
        'discriminatorField' => 'discriminator'
    ];

    /**
     * Tables cache.
     *
     * @var array
     */
    protected array $childTables = [];

    /**
     * Gets a STI table.
     *
     * @param  string|ArrayAccess|array $subject Discriminator value or an entity.
     * @return \Cake\ORM\Table
     */
    public function stiTable(string|ArrayAccess|array $subject): Table
    {
        if (is_array($subject) || $subject instanceof ArrayAccess) {
            $property = $this->_config['discriminatorField'];
            if (isset($subject[$property])) {
                $discriminator = $subject[$property];
            } else {
                return $this->_table;
            }
        } else {
            $discriminator = $subject;
        }

        if (!array_key_exists($discriminator, $this->childTables)) {
            $table = $this->findInTableMap($discriminator);

            if (!$table) {
                $table = $this->findInDiscriminatorMap($discriminator);
            }
            if (!$table) {
                $table = $this->_table;
            }

            $this->addStiTable($discriminator, $table);
        }

        return $this->childTables[$discriminator];
    }

    /**
     * Adds a table to STI cache.
     *
     * @param  string                       $discriminator Discriminator.
     * @param  \Cake\ORM\Table|string|array $table         Table instance or alias or config.
     * @return \Cake\ORM\Table
     */
    public function addStiTable(string $discriminator, Table|string|array $table): Table
    {
        if (!$table instanceof Table) {
            if (is_array($table)) {
                $options = $table;
                $alias = $table['alias'];
            } else {
                $options = [];
                $alias = $table;
            }

            $table = $this->getTableLocator()->get($alias, $options);
        }

        $this->childTables[$discriminator] = $table;

        return $this->_table;
    }

    /**
     * Creates new entity using STI table.
     *
     * @param  array|null $data    Data.
     * @param  array      $options Options.
     * @return \Cake\Datasource\EntityInterface
     */
    public function newStiEntity(?array $data = null, array $options = []): EntityInterface
    {
        $table = $this->stiTable($data);

        return $table->newEntity($data, $options);
    }

    /**
     * BeforeFind callback - converts entities based on STI tables.
     *
     * @param  \Cake\Event\Event $event   Event.
     * @param  \Cake\ORM\Query   $query   Query.
     * @param  \ArrayAccess      $options Options.
     * @return void
     */
    public function beforeFind(EventInterface $event, Query $query, ArrayAccess $options): void
    {
        if (!$query->isHydrationEnabled()) {
            return;
        }
        $query->formatResults(
            function ($results) {
                return $results->map(
                    function (EntityInterface $row) {
                        if ($row instanceof CopyableEntityInterface) {
                            $table = $this->stiTable($row);
                            $entityClass = $table->getEntityClass();

                            $row = new $entityClass(
                                $row->copyProperties(), [
                                'markNew' => $row->isNew(),
                                'markClean' => true,
                                'guard' => false,
                                'source' => $table->getRegistryAlias()
                                ]
                            );
                        }

                        return $row;
                    }
                );
            }
        );
    }

    /**
     * Searches for a match in tableMap.
     *
     * @param  string $discriminator Discriminator.
     * @return string
     */
    protected function findInTableMap(string $discriminator): string
    {
        $map = $this->_config['tableMap'];
        foreach ($map as $table => $rules) {
            if ($this->matches($discriminator, (array)$rules)) {
                return $table;
            }
        }
    }

    /**
     * Searches for a match in tableMap.
     *
     * @param  string $discriminator Discriminator.
     * @return mixed
     */
    protected function findInDiscriminatorMap(string $discriminator): mixed
    {
        $map = $this->_config['discriminatorMap'];
        foreach ($map as $rule => $table) {
            if ($this->matches($discriminator, (array)$rule)) {
                return $table;
            }
        }
    }
}
