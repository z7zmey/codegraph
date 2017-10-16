<?php
/** 
 * Copyright © 2017 Slizov Vadim <z7zmey@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Worker;

//use Worker\CfgVisitor\CfgVisitor;
use Worker\CfgVisitor\ResolveLiteralTypeVisitor;
use Worker\CfgVisitor\ResolveVariableTypeVisitor;
use PHPCfg\Parser;

class CfgParser
{
    private $client;
    
    public function __construct(Client $client, Parser $parser)
    {
        $this->client = $client;
        $this->parser = $parser;
    }
    
    public function processFileCfg($file)
    {
        $astData = $this->client->readMessage();
        
        $methodsReturnTypes = $this->getMethodsReturnTypes($astData['Methods']);
        $propertyTypes = $this->getPropertyTypes($astData['Properties']);
        $classExtends = $this->getClassExtends($astData['Classes']);
    
        $script = $this->parser->parse(file_get_contents($file), $file);
    
        $traverser = new \PHPCfg\Traverser();
        $traverser->addVisitor(new \PHPCfg\Visitor\Simplifier());
        $traverser->addVisitor(new ResolveLiteralTypeVisitor());
        $traverser->addVisitor(new ResolveVariableTypeVisitor($this->client, $methodsReturnTypes, $classExtends, $propertyTypes));
//        $traverser->addVisitor(new CfgVisitor($this->client));
        $traverser->traverse($script);
    }
    
    private function getMethodsReturnTypes(array $methods): array
    {
        $data = [];
        foreach ($methods as $method) {
            $name = $method['id'];
            $types = $method['types'];
            $data[$name] = (array)$types;
        }
        
        return $data;
    }
    
    private function getPropertyTypes(array $props): array
    {
        $data = [];
        foreach ($props as $property) {
            $name = $property['name'];
            $types = $property['types'];
            $data[$name] = $types;
        }
    
        return $data;
    }
    
    private function getClassExtends(array $classes): array
    {
        $data = [];
        foreach ($classes as $class) {
            $nsName = $class['name'];
            $data[$nsName] = $class['extends'];
        }
    
        return $data;
    }
}