<?php

namespace Spmartin\TableInheritance\Model\Behavior;

use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class StiBehavior extends Behavior
{

    use MatchesTrait;

    /**
     * Default options.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'discriminatorField' => 'discriminator',
        'discriminator' => null,
        'table' => null,
        'checkRules' => true,
        'acceptedDiscriminators' => []
    ];

    /**
     * Discriminator value.
     *
     * @var string
     */
    protected string $_discriminator;

    /**
     * Accepted discriminators.
     *
     * @var array
     */
    protected array $_acceptedDiscriminators = [];

    /**
     * Initialize method.
     *
     * @param  array $config Config.
     * @return void
     */
    public function initialize(array $config): void
    {
        if ($this->_config['table'] !== null) {
            $this->_table->setTable($this->_config['table']);
        }
        if ($this->_config['discriminator'] !== null) {
            $this->setDiscriminator($this->_config['discriminator']);
        }
    }

    /**
     * Returns default discriminator value.
     * If no discriminator has been set table alias is returned.
     *
     * @return string
     */
    public function getDiscriminator(): string
    {
        if ($this->_discriminator === null) {
            $this->_discriminator = $this->_table->getAlias();
        }

        return $this->_discriminator;
    }

    /**
     * Sets discriminator value.
     *
     * @param  string $discriminator Discriminator value.
     * @return \Cake\ORM\Table
     */
    public function setDiscriminator(string $discriminator): Table
    {
        $this->_discriminator = $discriminator;

        return $this->_table;
    }

    /**
     * Returns accepted discriminators.
     *
     * @return array
     */
    public function acceptedDiscriminators(): array
    {
        if (!$this->_acceptedDiscriminators) {
            $accepted = $this->_config['acceptedDiscriminators'];
            if (!$accepted) {
                $accepted = $this->getDiscriminator();
            }

            $this->_acceptedDiscriminators = (array)$accepted;
        }

        return $this->_acceptedDiscriminators;
    }

    /**
     * Checks whether a discriminator is accepted.
     *
     * @param  string $discriminator Discriminator value.
     * @return bool
     */
    public function isAcceptedDiscriminator(string $discriminator): bool
    {
        return $this->matches($discriminator, $this->acceptedDiscriminators());
    }

    /**
     * Adds an accepted discriminator.
     *
     * @param  string $discriminator Discriminator value.
     * @return \Cake\ORM\Table
     */
    public function addAcceptedDiscriminator(string $discriminator): TableRegistry
    {
        $this->_acceptedDiscriminators[] = $discriminator;

        return $this->_table;
    }

    /**
     * buildRules callback.
     *
     * @param  \Cake\Event\Event      $event Event.
     * @param  \Cake\ORM\RulesChecker $rules Rules.
     * @return void
     */
    public function buildRules(Event $event, RulesChecker $rules): void
    {
        if ($this->_config['checkRules']) {
            $rule = [$this, 'checkRules'];
            $rules->add(
                $rule, 'discriminator', [
                'errorField' => $this->_config['discriminatorField']
                ]
            );
        }
    }

    /**
     * beforeSave callback.
     *
     * @param  \Cake\Event\Event                $event  Event.
     * @param  \Cake\Datasource\EntityInterface $entity Entity.
     * @return void
     */
    public function beforeSave(Event $event, EntityInterface $entity): void
    {
        $field = $this->_config['discriminatorField'];
        if ($entity->isNew() && !$entity->has($field)) {
            $discriminator = $this->getDiscriminator();
            $entity->set($field, $discriminator);
        }
    }

    /**
     * beforeFind callback.
     *
     * @param  \Cake\Event\Event $event Event
     * @param  \Cake\ORM\Query   $query Query
     * @return void
     */
    public function beforeFind(Event $event, Query $query): void
    {
        $query->where(
            function ($exp) {
                return $exp->or($this->conditions());
            }
        );
    }

    /**
     * beforeDelete callback.
     *
     * @param  \Cake\Event\Event                $event  Event.
     * @param  \Cake\Datasource\EntityInterface $entity Entity.
     * @return bool
     */
    public function beforeDelete(Event $event, EntityInterface $entity): bool
    {
        $discriminatorField = $this->_config['discriminatorField'];

        if ($entity->has($discriminatorField) && !$this->isAcceptedDiscriminator($entity->get($discriminatorField))) {
            $event->stopPropagation();

            return false;
        }
    }

    /**
     * checkRules rule.
     *
     * @param  \Cake\Datasource\EntityInterface $entity Entity.
     * @return bool
     */
    public function checkRules(EntityInterface $entity): bool
    {
        $field = $this->_config['discriminatorField'];

        if ($entity->isDirty($field)) {
            return $this->matches($entity->get($field), $this->acceptedDiscriminators());
        }

        return true;
    }

    /**
     *
     * @return array
     */
    protected function conditions(): array
    {
        $field = $this->_table->aliasField($this->_config['discriminatorField']) . ' LIKE';

        $conditions = [];
        foreach ($this->acceptedDiscriminators() as $discriminator) {
            $discriminator = str_replace('*', '%', $discriminator);
            $conditions[][$field] = $discriminator;
        }

        return $conditions;
    }
}
