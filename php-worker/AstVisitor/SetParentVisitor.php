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

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class SetParentVisitor extends NodeVisitorAbstract
{
    protected $parent;
    protected $parentKey;
    
    public function beforeTraverse(array $nodes)
    {
        $this->handleArray($nodes);
    }
    
    public function enterNode(Node $node)
    {
        $this->parent = $node;
        
        foreach ($node->getSubNodeNames() as $key) {
            $this->parentKey = $key;
            $subNode = $node->$key;

            if (is_array($subNode)) {
                $this->handleArray($subNode);
            } elseif ($subNode instanceof Node) {
                $this->setParentInfo($subNode);
            }
        }
    }

    protected function handleArray(array $subNodes, array $index = []) {
        foreach ($subNodes as $i => &$subNode) {
            $subIndex = $index;
            $subIndex[] = $i;
            
            if (is_array($subNode)) {
                $subNode = $this->handleArray($subNode, $subIndex);
            } elseif ($subNode instanceof Node) {
                $this->setParentInfo($subNode, $subIndex);
            }
        }
    }
    
    protected function setParentInfo(Node $subNode, array $index = [])
    {
        if ($this->parent !== null) {
            /** @noinspection PhpUndefinedFieldInspection */
            $subNode->parent = $this->parent;
        }
        
        if ($this->parentKey !== null) {
            /** @noinspection PhpUndefinedFieldInspection */
            $subNode->parentKey = $this->parentKey;
        }
        
        if (count($index)) {
            /** @noinspection PhpUndefinedFieldInspection */
            $subNode->index = implode(':', $index);
        }
    }
}
