<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\unit\Espo\Tools;

use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Tools\FieldManager\FieldManager;
use Espo\Core\InjectableFactory;

use PHPUnit\Framework\TestCase;
use tests\unit\ReflectionHelper;

class FieldManagerTest extends TestCase
{
    private FieldManager $fieldManager;

    private $reflection;

    protected function setUp() : void
    {
        $this->metadata = $this->createMock(Metadata::class);
        $this->language = $this->createMock(Language::class);
        $this->baseLanguage = $this->createMock(Language::class);
        $this->defaultLanguage = $this->createMock(Language::class);

        $this->metadataHelper = $this->createMock(Metadata\Helper::class);

        $this->fieldManager = new FieldManager(
            $this->createMock(InjectableFactory::class),
            $this->metadata,
            $this->language,
            $this->baseLanguage,
            $this->metadataHelper
        );

        $this->reflection = new ReflectionHelper($this->fieldManager);
    }

    public function testCreateExistingField()
    {
        $this->expectException('Espo\Core\Exceptions\Conflict');

        $data = [
            "type" => "varchar",
            "maxLength" => "50",
        ];

        $this->metadata
            ->expects($this->once())
            ->method('getObjects')
            ->will($this->returnValue($data));

        $this->fieldManager->create('CustomEntity', 'varName', $data);
    }

    public function testUpdateCoreField()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => 100,
            "label" => "Modified Name",
        );

        $existingData = (object) [
            "type" => "varchar",
            "maxLength" => 50,
            "label" => "Name",
        ];

        $map = array(
            [['entityDefs', 'Account', 'fields', 'name', 'type'], null, $data['type']],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->language
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->will($this->returnValue($existingData));

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->will($this->returnValue(json_decode('{
               "params":[
                  {
                     "name":"required",
                     "type":"bool",
                     "default":false
                  },
                  {
                     "name":"default",
                     "type":"varchar"
                  },
                  {
                     "name":"maxLength",
                     "type":"int"
                  },
                  {
                     "name":"trim",
                     "type":"bool",
                     "default": true
                  },
                  {
                     "name": "options",
                     "type": "multiEnum"
                  },
                  {
                     "name":"audited",
                     "type":"bool"
                  },
                  {
                     "name":"readOnly",
                     "type":"bool"
                  }
               ],
               "filter": true,
               "personalData": true,
               "textFilter": true,
               "fullTextSearch": true
            }', true)));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getCustom')
            ->will($this->returnValue((object) []));

        $this->fieldManager->update('Account', 'name', $data);
    }

    public function testUpdateCoreFieldWithNoChanges()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => 50,
            "label" => "Name",
        );

        $map = array(
            [['entityDefs', 'Account', 'fields', 'name', 'type'], null, $data['type']],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->metadata
            ->expects($this->never())
            ->method('set');

        $this->language
            ->expects($this->once())
            ->method('save');

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->will($this->returnValue(json_decode('{
               "params":[
                  {
                     "name":"required",
                     "type":"bool",
                     "default":false
                  },
                  {
                     "name":"default",
                     "type":"varchar"
                  },
                  {
                     "name":"maxLength",
                     "type":"int"
                  },
                  {
                     "name":"trim",
                     "type":"bool",
                     "default": true
                  },
                  {
                     "name": "options",
                     "type": "multiEnum"
                  },
                  {
                     "name":"audited",
                     "type":"bool"
                  },
                  {
                     "name":"readOnly",
                     "type":"bool"
                  }
               ],
               "filter": true,
               "personalData": true,
               "textFilter": true,
               "fullTextSearch": true
            }', true)));

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->will($this->returnValue((object) $data));

        $this->metadata
            ->expects($this->exactly(1))
            ->method('getCustom')
            ->will($this->returnValue((object) []));

        $this->metadata
            ->expects($this->never())
            ->method('saveCustom');

        $this->fieldManager->update('Account', 'name', $data);
    }

    public function dddtestUpdateCustomFieldIsNotChanged()
    {
        $data = [
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
        ];

        $map = [
            ['entityDefs.CustomEntity.fields.varName', [], $data],
            ['entityDefs.CustomEntity.fields.varName.type', null, $data['type']],
            [['entityDefs', 'CustomEntity', 'fields', 'varName'], null, $data],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        ];

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->metadata
            ->expects($this->never())
            ->method('set')
            ->will($this->returnValue(true));

        $this->metadata
            ->expects($this->exactly(1))
            ->method('getCustom')
            ->will($this->returnValue((object) []));

        $this->fieldManager->update('CustomEntity', 'varName', $data);
    }

    public function testUpdateCustomField()
    {
        $data = [
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
        ];

        $map = [
            ['entityDefs.CustomEntity.fields.varName.type', null, $data['type']],
            [['entityDefs', 'CustomEntity', 'fields', 'varName'], null, $data],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        ];

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->will($this->returnValue((object) $data));

        $this->metadata
            ->expects($this->once())
            ->method('saveCustom')
            ->will($this->returnValue(true));

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->will($this->returnValue(json_decode('{
               "params":[
                  {
                     "name":"required",
                     "type":"bool",
                     "default":false
                  },
                  {
                     "name":"default",
                     "type":"varchar"
                  },
                  {
                     "name":"maxLength",
                     "type":"int"
                  },
                  {
                     "name":"trim",
                     "type":"bool",
                     "default": true
                  },
                  {
                     "name": "options",
                     "type": "multiEnum"
                  },
                  {
                     "name":"audited",
                     "type":"bool"
                  },
                  {
                     "name":"readOnly",
                     "type":"bool"
                  }
               ],
               "filter": true,
               "personalData": true,
               "textFilter": true,
               "fullTextSearch": true
            }', true)));

        $data = array(
            "type" => "varchar",
            "maxLength" => "150",
            "required" => true,
            "isCustom" => true,
        );

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getCustom')
            ->will($this->returnValue((object) []));

        $this->fieldManager->update('CustomEntity', 'varName', $data);
    }

    public function testRead()
    {
        $data = [
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
            "label" => 'Var Name',
        ];

        $this->metadata
            ->expects($this->once())
            ->method('getObjects')
            ->will($this->returnValue((object) $data));

        $this->language
            ->expects($this->once())
            ->method('translate')
            ->will($this->returnValue('Var Name'));

        $this->assertEquals($data, $this->fieldManager->read('Account', 'varName'));
    }

    public function testNormalizeDefs()
    {
        $input1 = 'fieldName';
        $input2 = [
            "type" => "varchar",
            "maxLength" => "50",
        ];

        $result = (object) [
            'fields' => (object) [
                'fieldName' => (object) [
                    "type" => "varchar",
                    "maxLength" => "50",
                ],
            ],
        ];
        $this->assertEquals(
            $result,
            $this->reflection->invokeMethod('normalizeDefs', ['CustomEntity', $input1, $input2])
        );
    }
}
