<?php

namespace Spmartin\TableInheritance\Test\TestCase\Model\Behavior;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Spmartin\TableInheritance\Test\Mock\Author;
use Spmartin\TableInheritance\Test\Mock\Editor;
use Spmartin\TableInheritance\Test\Mock\Reader;
use Spmartin\TableInheritance\Test\Mock\User;

class StiParentBehaviorTest extends TestCase
{
    public array $fixtures = [
        'plugin.Spmartin\TableInheritance.Users'
    ];

    /**
     *
     * @var Table
     */
    public Table $table;

    public function setUp(): void
    {
        parent::setUp();

        $this->entityMap = [
            'Authors' => Author::class,
            'Users' => User::class,
            'Editors' => Editor::class,
            'Readers' => Reader::class,
            'Subscribers' => Reader::class,
            '' => User::class
        ];

        $this->table = TableRegistry::getTableLocator()->get('Users');
        $this->table->setEntityClass(User::class);

        $authors = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $editors = TableRegistry::getTableLocator()->get(
            'Editors', [
            'table' => 'users'
            ]
        );
        $readers = TableRegistry::getTableLocator()->get(
            'Readers', [
            'table' => 'users'
            ]
        );

        $authors->addBehavior('Spmartin/TableInheritance.Sti');
        $editors->addBehavior('Spmartin/TableInheritance.Sti');
        $readers->addBehavior('Spmartin/TableInheritance.Sti');
        $this->table->addBehavior(
            'Spmartin/TableInheritance.StiParent', [
            'discriminatorMap' => [
                'Authors' => 'Authors',
                'Editors' => 'Editors'
            ],
            'tableMap' => [
                'Readers' => [
                    'Readers',
                    'Subscribers'
                ]
            ]
            ]
        );

        $authors->setEntityClass(Author::class);
        $editors->setEntityClass(Editor::class);
        $readers->setEntityClass(Reader::class);
    }

    public function testStiTable()
    {
        $this->table->behaviors()->get('StiParent')->setConfig(
            'tableMap', [
            'Readers' => 'reader_*'
            ], false
        );
        $this->table->behaviors()->get('StiParent')->setConfig(
            'discriminatorMap', [
            '*author' => TableRegistry::getTableLocator()->get('Authors')
            ], false
        );

        $map = [
            'reader_1' => 'Readers',
            'reader_2' => 'Readers',
            'super_author' => 'Authors',
            'bestselling-author' => 'Authors',
            'other' => 'Users',
            '' => 'Users',
        ];

        foreach ($map as $discriminator => $alias) {
            $entity = $this->table->newEntity(
                [
                'discriminator' => $discriminator
                ]
            );
            $table = $this->table->stiTable($entity);
            $this->assertEquals($alias, $table->getAlias());
        }
    }

    public function tearDown(): void
    {
        parent::tearDown();
        TableRegistry::getTableLocator()->clear();
    }

    public function testNewStiEntity()
    {
        foreach ($this->entityMap as $discriminator => $class) {
            $data = [
                'discriminator' => $discriminator
            ];

            $entity = $this->table->newStiEntity($data);
            $this->assertInstanceOf($class, $entity);
        }
    }

    public function testFind()
    {
        $entities = [];
        foreach ($this->entityMap as $discriminator => $class) {
            $data = [
                'discriminator' => $discriminator
            ];
            $entities[] = $this->table->newEntity($data);
        }

        $this->table->saveMany($entities);

        $found = $this->table->find()->toArray();
        $this->assertCount(8, $found);

        foreach ($found as $entity) {
            $class = $this->entityMap[$entity->discriminator];
            $this->assertInstanceOf($class, $entity);
        }
    }
}
