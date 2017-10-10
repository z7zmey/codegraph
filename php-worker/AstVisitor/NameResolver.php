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

namespace Worker\AstVisitor;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\UseUse;

class NameResolver extends \PhpParser\NodeVisitor\NameResolver
{
    private $caseSensiveAliases;
    
    public function getNameSpace()
    {
        return (string)$this->namespace;
    }
    
    public function getContext(): array
    {
        $result = [];
        
        if (!$this->caseSensiveAliases) {
            return $result;
        }
        
        foreach ($this->caseSensiveAliases as $aliases) {
            foreach ($aliases as $name => $alias) {
                $result[$name] = (string)$alias;
            }
        }
        
        return $result;
    }
    
    public function enterNode(Node $node)
    {
        parent::enterNode($node);
        
        if ($node instanceof Node\Stmt\ClassMethod) {
            $node->namespacedName = $node->parent->namespacedName . '::' . $node->name;
        }
    }
    
    protected function addAlias(UseUse $use, $type, Name $prefix = null)
    {
        parent::addAlias($use, $type, $prefix);
        
        $name = $prefix ? Name::concat($prefix, $use->name) : $use->name;
        $type |= $use->type;
        
        $aliasName = $use->alias;
        
        $this->caseSensiveAliases[$type][$aliasName] = $name;
    }
}