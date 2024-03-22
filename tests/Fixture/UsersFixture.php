<?php

namespace Spmartin\TableInheritance\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture
{

    /**
     * fields property
     */
    public array $fields = [
        'id' => ['type' => 'integer'],
        'discriminator' => ['type' => 'string'],
        'name' => ['type' => 'string'],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    public array $records = [
        [
            'id' => 1,
            'name' => 'John',
            'discriminator' => 'Authors'
        ],
        [
            'id' => 2,
            'name' => 'Jane',
            'discriminator' => 'Editors'
        ]
    ];
}
