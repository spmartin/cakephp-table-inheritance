<?php

namespace Spmartin\TableInheritance\Test\TestCase\Model\Behavior;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class StiBehaviorTest extends TestCase
{
    public array $fixtures = [
        'plugin.Spmartin\TableInheritance.Users'
    ];

    public function tearDown(): void
    {
        parent::tearDown();
        TableRegistry::getTableLocator()->clear();
    }

    public function testDiscriminator()
    {
        $table = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $table->addBehavior('Spmartin/TableInheritance.Sti');

        $this->assertEquals('Authors', $table->getDiscriminator());
        $this->assertEquals('author', $table->setDiscriminator('author'));

        $table = TableRegistry::getTableLocator()->get(
            'Editors', [
            'table' => 'users'
            ]
        );
        $table->addBehavior(
            'Spmartin/TableInheritance.Sti', [
            'discriminator' => 'editor'
            ]
        );

        $this->assertEquals('editor', $table->getDiscriminator());
    }

    public function testAcceptedDiscriminators()
    {
        $table = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $table->addBehavior('Spmartin/TableInheritance.Sti');

        $accepted = $table->acceptedDiscriminators();
        $this->assertContains('Authors', $accepted);

        $this->assertTrue($table->isAcceptedDiscriminator('Authors'));
        $this->assertFalse($table->isAcceptedDiscriminator('Editors'));

        $table->addAcceptedDiscriminator('author_*');
        $this->assertTrue($table->isAcceptedDiscriminator('author_foo'));
        $this->assertTrue($table->isAcceptedDiscriminator('author_bar'));
        $this->assertFalse($table->isAcceptedDiscriminator('editor'));
    }

    public function testSave()
    {
        $table = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $table->addBehavior('Spmartin/TableInheritance.Sti');

        $entity = $table->newEntity(
            [
            'name' => 'Robert'
            ]
        );
        $table->save($entity);

        $this->assertEmpty($entity->getErrors());
        $this->assertEquals('Authors', $entity->discriminator);

        $entity2 = $table->newEntity(
            [
            'name' => 'Robert',
            'discriminator' => 'Editors'
            ]
        );
        $table->save($entity2);

        $this->assertArrayHasKey('discriminator', $entity2->getErrors());
        $this->assertEquals('Editors', $entity2->discriminator);
    }

    public function testSaveNoRules()
    {
        $table = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $table->addBehavior(
            'Spmartin/TableInheritance.Sti', [
            'checkRules' => false
            ]
        );

        $entity = $table->newEntity(
            [
            'name' => 'Robert',
            'discriminator' => 'Editors'
            ]
        );
        $table->save($entity);

        $this->assertEmpty($entity->getErrors());
        $this->assertEquals('Editors', $entity->discriminator);
    }

    public function testSaveWildcard()
    {
        $table = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $table->addBehavior(
            'Spmartin/TableInheritance.Sti', [
            'acceptedDiscriminators' => 'author_*'
            ]
        );

        $entity = $table->newEntity(
            [
            'name' => 'Robert',
            'discriminator' => 'author_foo'
            ]
        );
        $table->save($entity);

        $this->assertEmpty($entity->getErrors());
        $this->assertEquals('author_foo', $entity->discriminator);
    }

    public function testFind()
    {
        $authors = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $authors->addBehavior('Spmartin/TableInheritance.Sti');

        $authorResults = $authors->find();
        $this->assertCount(1, $authorResults);

        $editors = TableRegistry::getTableLocator()->get(
            'Editors', [
            'table' => 'users'
            ]
        );
        $editors->addBehavior('Spmartin/TableInheritance.Sti');

        $editorResults = $editors->find();
        $this->assertCount(1, $editorResults);

        $subscribers = TableRegistry::getTableLocator()->get(
            'Subscribers', [
            'table' => 'users'
            ]
        );
        $subscribers->addBehavior('Spmartin/TableInheritance.Sti');

        $subscriberResults = $subscribers->find();
        $this->assertCount(0, $subscriberResults);
    }

    public function testFindWildcard()
    {
        $authors = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $authors->addBehavior(
            'Spmartin/TableInheritance.Sti', [
            'acceptedDiscriminators' => 'Auth*'
            ]
        );

        $authorResults = $authors->find();
        $this->assertCount(1, $authorResults);

        $authors->addAcceptedDiscriminator('Edit*');

        $authorResults = $authors->find();
        $this->assertCount(2, $authorResults);
    }

    public function testDelete()
    {
        $table = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $table->addBehavior('Spmartin/TableInheritance.Sti');

        $entity = $table->get(1);
        $deleted = $table->delete($entity);
        $this->assertTrue($deleted);

        $entity = TableRegistry::getTableLocator()->get('Users')->get(2);
        $deleted = $table->delete($entity);
        $this->assertFalse($deleted);
    }

    public function testDeleteWildcard()
    {
        $table = TableRegistry::getTableLocator()->get(
            'Authors', [
            'table' => 'users'
            ]
        );
        $table->addBehavior(
            'Spmartin/TableInheritance.Sti', [
            'acceptedDiscriminators' => 'Auth*'
            ]
        );

        $entity = $table->get(1);
        $deleted = $table->delete($entity);
        $this->assertTrue($deleted);

        $entity = TableRegistry::getTableLocator()->get('Users')->get(2);
        $deleted = $table->delete($entity);
        $this->assertFalse($deleted);
    }
}
