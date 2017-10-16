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

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use Worker\Client;

class GraphVisitor extends NodeVisitorAbstract
{
    protected $indent;
    protected $stack;
    protected $fileRecord;
    protected $client;
    
    /**
     * @var string
     */
    private $file;
    
    public function __construct(Client $client, string $file)
    {
        $this->stack = new \SplStack();
        
        $this->client = $client;
        $this->file = $file;
    }

    public function beforeTraverse(array $nodes) 
    {
        
    }
    
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $data = [
                'classes' => [
                    [
                        'name' => (string)$node->namespacedName,
                        'startLine' => $node->getAttribute('startLine'),
                        'endLine' => $node->getAttribute('endLine'),
                        'file' => $this->file,
                        'extends' => $node->extends,
                        'implements' => $node->implements,
                        'isAbstract' => $node->isAbstract(),
                    ]
                ]
            ];
        
            $this->client->sendMessage($data, 1);
        }
        
        if ($node instanceof Node\Stmt\Interface_) {
            $data = [
                'interfaces' => [
                    [
                        'name' => (string)$node->namespacedName,
                        'startLine' => $node->getAttribute('startLine'),
                        'endLine' => $node->getAttribute('endLine'),
                        'file' => $this->file,
                        'extends' => $node->extends,
                    ]
                ]
            ];
        
            $this->client->sendMessage($data, 1);
        }
    
        if ($node instanceof Node\Stmt\ClassMethod) {
            $data = [
                'methods' => [
                    [
                        'id' => $node->namespacedName,
                        'name' => $node->name,
                        'startLine' => $node->getAttribute('startLine'),
                        'endLine' => $node->getAttribute('endLine'),
                        'class' => (string)$node->parent->namespacedName,
                        'types' => $node->returnTypes,
                        'isAbstract' => $node->isAbstract() || $node->parent instanceof Node\Stmt\Interface_,
                    ]
                ]
            ];
        
            $this->client->sendMessage($data, 1);
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }
    
        if ($node instanceof Node\Stmt\PropertyProperty) {
            $data = [
                'properties' => [
                    [
                        'name' => $node->parent->parent->namespacedName . "::" . $node->name,
                        'startLine' => $node->getAttribute('startLine'),
                        'endLine' => $node->getAttribute('endLine'),
                        'class' => (string)$node->parent->parent->namespacedName,
                        'types' => $node->returnTypes
                    ]
                ]
            ];
        
            $this->client->sendMessage($data, 1);
        }
    }
    
    public function leaveNode(Node $node)
    {
    }
    
    public function afterTraverse(array $nodes)
    {
    }
}
