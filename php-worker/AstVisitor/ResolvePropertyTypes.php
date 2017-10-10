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
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Context;
use PhpParser\Comment\Doc;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class ResolvePropertyTypes extends NodeVisitorAbstract
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
        if (!$node instanceof Node\Stmt\Property) {
            return;
        }
    
        $docComment = $node->getDocComment();
        $returnTypes = $this->getDocCommentVarTypes($docComment);
        
        foreach ($node->props as $property) {
            $property->returnTypes = $returnTypes;
            
            if ($property->default instanceof Node\Expr\Array_) {
                $property->returnTypes[] = 'array';
            }
    
            if ($property->default instanceof Node\Scalar\String_) {
                $property->returnTypes[] = 'string';
            }
    
            if ($property->default instanceof Node\Scalar\LNumber) {
                $property->returnTypes[] = 'int';
            }
    
            if ($property->default instanceof Node\Scalar\DNumber) {
                $property->returnTypes[] = 'double';
            }
            
            //TODO: добавить проверки на остальные типы
    
            $property->returnTypes = array_unique($property->returnTypes);
        }
    }
    
    protected function getDocCommentVarTypes(Doc $docComment = null): array
    {
        if (!$docComment || !$docComment->getText()) {
            return [];
        }
        
        $returnTypes = [];
        
        $docText = $docComment ? $docComment->getText() : '';
        $docText = preg_replace('/^\s*\*\s*@see.*$/m', '', $docText); // too often have wrong fsqen
        $context = new Context($this->nameResolver->getNameSpace(), $this->nameResolver->getContext());
        
        $factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $docBlock = $factory->create($docText, $context);
        
        $varTags = $docBlock->getTagsByName('var');
        
        /** @var Var_ $varTag */
        foreach ($varTags as $varTag) {
            $type = $varTag->getType();
            
            if ($type instanceof Compound) {
                $i = 0;
                while ($subType = $type->get($i++))
                {
                    $returnTypes[] = ltrim((string)$subType, '\\');
                }
                
                continue;
            }
    
            $returnTypes[] = ltrim((string)$type, '\\');
        }
        
        return $returnTypes;
    }
}
