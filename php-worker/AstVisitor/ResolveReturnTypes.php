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

use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Context;
use PhpParser\Comment\Doc;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class ResolveReturnTypes extends NodeVisitorAbstract
{
    /**
     * @var NameResolver
     */
    private $nameResolver;
    
    public function __construct(NameResolver $nameResolver)
    {
    
        $this->nameResolver = $nameResolver;
    }
    
    public function enterNode(Node $node)
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return;
        }
        
        if ($node->returnType) {
            $node->returnTypes = [$node->returnType];
            return;
        }
    
        $docComment = $node->getDocComment();
        $node->returnTypes = $this->getDocCommentReturnTypes($docComment);
    }
    
    protected function getDocCommentReturnTypes(Doc $docComment = null): array
    {
        if (!$docComment || !$docComment->getText()) {
            return [];
        }
        
        $returnTypes = [];
        
        $docText = $docComment ? $docComment->getText() : '';
        $docText = preg_replace('/^\s*\*\s*@see.*$/m', '', $docText); // too often have wrong fsqen
        $context = new Context($this->nameResolver->getNameSpace(), $this->nameResolver->getContext());
        
        $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $docBlock = $factory->create($docText, $context);
        
        $returnTags = $docBlock->getTagsByName('return');
        
        /** @var Return_ $returnTag */
        foreach ($returnTags as $returnTag) {
            $type = $returnTag->getType();
            
            if ($type instanceof Compound) {
                $i = 0;
                while ($subType = $type->get($i++)) {
                    $returnTypes[] = ltrim((string)$subType, '\\');
                }
                
                continue;
            }
    
            $returnTypes[] = ltrim((string)$type, '\\');
        }
        
        return $returnTypes;
    }
}
