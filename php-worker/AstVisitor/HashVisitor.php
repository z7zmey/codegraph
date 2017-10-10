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

use ParserAst\Graph;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class HashVisitor extends NodeVisitorAbstract
{
    /**
     * @var string
     */
    private $file;
    
    public function __construct(string $file)
    {
    
        $this->file = $file;
    }
    
    public function enterNode(Node $node)
    {
        $parentHash = $this->file;
        if (isset($node->parent) && $node->parent->hash) {
            $parentHash = $node->parent->hash;
        }
        
        $parentKey = $node->parentKey ?? '';
        $index = $node->index ?? '';
        
        $str = implode(';', [$parentHash, $parentKey, $index]);
        $node->hash = md5($str);
    }
}
