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
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;

class ExtendResolver extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $node->extends = (string)$node->extends;
        
            $implements = [];
            foreach ($node->implements as $implement) {
                $implements[] = (string)$implement;
            }
            $node->implements = $implements;
        }
        
        if ($node instanceof Interface_) {
            $extends = [];
            foreach ((array)$node->extends as $extend) {
                $extends[] = (string)$extend;
            }
            $node->extends = $extends;
        }
    }
}