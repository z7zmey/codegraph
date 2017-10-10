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

namespace Worker\CfgVisitor;

use PHPCfg\Block;
use PHPCfg\Func;
use PHPCfg\Op;
use PHPCfg\Operand;
use PHPCfg\Script;
use PHPCfg\Visitor;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\Types\Compound;
use PhpParser\Comment\Doc;
use Worker\Client;

class ResolveVariableTypeVisitor implements Visitor
{
    /**
     * @var array
     */
    protected $methodsRetrurnTypes;
    
    /**
     * @var array
     */
    protected $classExtends;
    
    /**
     * @var array
     */
    protected $classPropertyTypes;
    
    /**
     * @var Func
     */
    protected $func;
    
    /**
     * @var array
     */
    protected $methodCallsRepo;
    
    /**
     * @var Client
     */
    private $client;
    
    public function __construct(Client $client, array $methodsReturnTypes, array $classExtends, array $classPropertyTypes) {
        $this->methodsRetrurnTypes = $methodsReturnTypes;
        $this->classExtends = $classExtends;
        $this->classPropertyTypes = $classPropertyTypes;
        $this->client = $client;
    }

    public function enterScript(Script $script)
    {
        // do nothing
    }

    public function leaveScript(Script $script)
    {
        // do nothing
    }

    public function enterFunc(Func $func)
    {
        $this->func = $func;
        $docComment = $func->callableOp ? $func->callableOp->getAttribute('doccomment') : '';
        foreach ($func->params as $param) {
            if ($param->type) {
                $param->result->type = [$param->type->value];
            }
            
            if (!$param->result->type) {
                $paramTypes = $this->getDocCommentParamTypes($docComment, $param->name->value);
                $param->result->type = $paramTypes ? $this->normaliseTypes($paramTypes) : null;
            }
            
            //$this->processOp($param);
        }
    }

    protected function getDocCommentParamTypes(Doc $docComment = null, string $var)
    {
        if (!$docComment) {
            return null;
        }

        if (!$docComment->getText()) {
            return null;
        }

        $paramTypes = [];

        $docText = $docComment ? $docComment->getText() : '';
        $docText = preg_replace('/^\s*\*\s*@see.*$/m', '', $docText); // too often have wrong fsqen
    
        $factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $docBlock = $factory->create($docText);

        $paramTags = $docBlock->getTagsByName('param');

        /** @var Param $paramTag */
        foreach ($paramTags as $paramTag) {
            if ($paramTag->getVariableName() !== $var) {
                continue;
            }
            $type = $paramTag->getType();

            if ($type instanceof Compound) {
                $i = 0;
                while ($subType = $type->get($i++))
                {
                    $paramTypes[] = (string)$subType;
                }

                continue;
            }

            $paramTypes[] = (string)$type;
        }

        return $paramTypes ?: null;
    }

    public function leaveFunc(Func $func)
    {
        // do nothing
    }

    public function enterBlock(Block $block, Block $prior = null)
    {
        foreach ($block->phi as $phi) {
            $this->processOp($phi);
        }
    }

    public function enterOp(Op $op, Block $block)
    {
        $this->processOp($op);
    }
    
    protected function processOp(Op $op)
    {
        if ($op instanceof Op\Phi) {
            $varTypes = array_column($op->vars, 'type');
            $aggr = [];
            foreach ($varTypes as $types) {
                if (!$types) {
                    continue;
                }
                foreach ($types as $type){
                    $aggr[] = $type;
                }
            }
            $types = array_unique($aggr);
            
            $op->result->type = $types;
            
            return;
        }
        
        // Expressions
        
        if ($op instanceof Op\Expr\Param) {
            if ($op->type) {
                $op->result->type = [$op->type->value];
            }
            return;
        }

        if ($op instanceof Op\Expr\New_) {
            $op->result->type = [$op->class->value];
    
            // TODO: сохранять в базу вызовы из файла (не из функции)
            if (!$this->func->callableOp) {
                return;
            }
    
            $callTo = sprintf("%s::__construct", $op->class->value);
            $from = $this->func->getScopedName();
            if (array_key_exists($from, $this->methodsRetrurnTypes) && array_key_exists($callTo, $this->methodsRetrurnTypes)) {
                $data = [
                    'methods' => [
                        [
                            'id' => $from,
                            'calls' => [$callTo],
                        ],
                    ],
                ];
                $this->client->sendMessage($data);
            }
            
            return;
        }

        if ($op instanceof Op\Expr\Assertion) {
//            $op->result->type = [$op->assertion->value->value];
            
            return ['boolean'];
        }

        if ($op instanceof Op\Expr\Assign || $op instanceof Op\Expr\AssignRef) {
            $op->var->type = $op->expr->type;
            $op->result->type = $op->expr->type;
            return;
        }

        if ($op instanceof Op\Expr\ArrayDimFetch) {
            // TODO: нужно найти выше присваивание массива и получить значение
            return;
        }

        if ($op instanceof Op\Expr\Exit_) {
            $op->result->type = $op->expr->type;
            return;
        }

        if ($op instanceof Op\Expr\UnaryMinus) {
            // если строка содержит число то приводит тип к числу.
            $op->result->type = $op->expr->type;
            return;
        }

        if ($op instanceof Op\Expr\UnaryPlus) {
            $op->result->type = $op->expr->type;
            return;
        }

        if ($op instanceof Op\Expr\Array_) {
            $op->result->type = ['array'];
            return;
        }

        if ($op instanceof Op\Expr\Empty_) {
            $op->result->type = ['boolean'];
            return;
        }

        if ($op instanceof Op\Expr\Isset_) {
            $op->result->type = ['boolean'];
            return;
        }

        if ($op instanceof Op\Expr\BooleanNot) {
            $op->result->type = ['boolean'];
            return;
        }

        if ($op instanceof Op\Expr\InstanceOf_) {
            $op->result->type = ['boolean'];
            return;
        }

        if ($op instanceof Op\Expr\Print_) {
            $op->result->type = ['integer'];
            return;
        }

        if ($op instanceof Op\Expr\BitwiseNot) {
            $op->result->type = $op->expr->type;
            return;
        }

        if ($op instanceof Op\Expr\Clone_) {
            $op->result->type = $op->expr->type;
            return;
        }

        if ($op instanceof Op\Expr\Closure) {
            $op->result->type = ['closure'];
            return;
        }

        if ($op instanceof Op\Expr\ConcatList) {
            $op->result->type = ['string'];
            return;
        }

        // Expression Cast
        
        $map = [
            Op\Expr\Cast\Array_::class => ['array'],
            Op\Expr\Cast\Bool_::class => ['boolean'],
            Op\Expr\Cast\Double::class => ['double'],
            Op\Expr\Cast\Int_::class => ['integer'],
            Op\Expr\Cast\Object_::class => ['object'],
            Op\Expr\Cast\Unset_::class => ['null'],
        ];
        
        $opCls = get_class($op);
        if (array_key_exists($opCls, $map)) {
            $op->result->type = $map[$opCls];
            return;
        }

        // Binary operation

        if ($op instanceof Op\Expr\BinaryOp\BitwiseAnd && $op->left->type === $op->right->type) {
            $op->result->type = $op->left->type;
            return;
        }

        if ($op instanceof Op\Expr\BinaryOp\BitwiseOr && $op->left->type === $op->right->type) {
            $op->result->type = $op->left->type;
            return;
        }

        if ($op instanceof Op\Expr\BinaryOp\BitwiseXor && $op->left->type === $op->right->type) {
            $op->result->type = $op->left->type;
            return;
        }

        if ($op instanceof Op\Expr\BinaryOp\Coalesce && $op->left->type === $op->right->type) {
            $op->result->type = $op->left->type;
            return;
        }

        if ($op instanceof Op\Expr\BinaryOp\ShiftLeft && $op->left->type === $op->right->type) {
            $op->result->type = $op->left->type;
            return;
        }

        if ($op instanceof Op\Expr\BinaryOp\ShiftRight && $op->left->type === $op->right->type) {
            $op->result->type = $op->left->type;
            return;
        }

        if ($op instanceof Op\Expr\BinaryOp\Spaceship && $op->left->type === $op->right->type) {
            $op->result->type = ['integer'];
            return;
        }

        $map = [
            Op\Expr\BinaryOp\Concat::class => ['string'],
            Op\Expr\BinaryOp\Mod::class => ['integer'],
            // TODO: доработать приведение типов матиматических операций
            Op\Expr\BinaryOp\Div::class => ['double'],
            Op\Expr\BinaryOp\Mul::class => ['double'],
            Op\Expr\BinaryOp\Pow::class => ['double'],
            Op\Expr\BinaryOp\Plus::class => ['double'],
            Op\Expr\BinaryOp\Minus::class => ['double'],
            
            Op\Expr\BinaryOp\Equal::class => ['boolean'],
            Op\Expr\BinaryOp\NotEqual::class => ['boolean'],
            Op\Expr\BinaryOp\Identical::class => ['boolean'],
            Op\Expr\BinaryOp\NotIdentical::class => ['boolean'],
            Op\Expr\BinaryOp\Greater::class => ['boolean'],
            Op\Expr\BinaryOp\GreaterOrEqual::class => ['boolean'],
            Op\Expr\BinaryOp\Smaller::class => ['boolean'],
            Op\Expr\BinaryOp\SmallerOrEqual::class => ['boolean'],
            Op\Expr\BinaryOp\LogicalXor::class => ['boolean'],
        ];

        $opCls = get_class($op);
        if (array_key_exists($opCls, $map) && $op->left->type === $op->right->type) {
            $op->result->type = $map[$opCls];
            return;
        }
        
        // functions call

        if ($op instanceof Op\Expr\MethodCall) {
            if ($op->var instanceof Operand\BoundVariable) {
                $clsNames = [$op->var->extra->value];
            } elseif ($op->var instanceof Operand\Temporary) {
                $clsNames = $op->var->type;
            } else {
                throw new \Exception('Ups');
            }
            
            if (!$clsNames) {
                return;
            }
            
            $method = $op->name->value;
    
            // TODO: сохранять в базу вызовы из файла (не из функции)
            if (!$this->func->callableOp) {
                return;
            }
            
            foreach ($clsNames as $clsName) {
                do {
                    $callTo =  $clsName . '::' . $method;
                    if (isset($this->methodsRetrurnTypes[$callTo])) {
                        $op->callTo = $callTo;
                        $returnTypes = $this->methodsRetrurnTypes[$callTo];
                        $normalisedTypes = $this->normaliseTypes($returnTypes);
                        $op->result->type = is_array($op->result->type) 
                            ? array_merge($op->result->type, $normalisedTypes)
                            : $normalisedTypes;
    
                        $from = $this->func->getScopedName();
                        if (array_key_exists($from, $this->methodsRetrurnTypes)) {
                            $data = [
                                'methods' => [
                                    [
                                        'id' => $from,
                                        'calls' => [$callTo],
                                    ],
                                ],
                            ];
                            $this->client->sendMessage($data);
                        }
    
                        break;
                    }
                } while ($clsName = ($this->classExtends[$clsName] ?? null));
            }
            
            return;
        }
        
        // other
        
        if ($op instanceof Op\Expr\PropertyFetch) {
            if ($op->var instanceof Operand\BoundVariable) {
                $clsNames = [$op->var->extra->value];
            } elseif ($op->var instanceof Operand\Temporary) {
                $clsNames = $op->var->type;
            } else {
                echo 'warning cfg variable class: ' . get_class($op->var);
                return;
            }
            
            if (!$clsNames) {
                return;
            }

            $property = $op->name->value;

            foreach ($clsNames as $clsName) {
                do {
                    if (isset($this->classPropertyTypes[$clsName . '::' . $property])) {
                        $returnTypes = $this->classPropertyTypes[$clsName . '::' . $property];
                        $normalisedTypes = $this->normaliseTypes($returnTypes);
                        $op->result->type = is_array($op->result->type)
                            ? array_merge($op->result->type, $normalisedTypes)
                            : $normalisedTypes;
                        break;
                    }
                } while ($clsName = ($this->classExtends[$clsName] ?? null));
            }
            
            return;
        }
    }
    
    protected function normaliseTypes(array $types) {
        $normalised = [];
        
        foreach ($types as $type) {
            $map = [
                'int' => 'integer',
                'bool' => 'boolean',
                'float' => 'double',
            ];
            
            if (array_key_exists($type, $map)) {
                $normalised[] = $map[$type];
                continue;
            }
            
            if (substr($type, -2) === '[]') {
                $normalised[] = 'array';
                continue;
            }
            
            $type = trim($type, '\\');
            
            $normalised[] = $type;
        }
        
        return $normalised;
    }

    public function leaveOp(Op $op, Block $block)
    {
        // do nothing
    }

    public function leaveBlock(Block $block, Block $prior = null)
    {
        // do nothing
    }

    public function skipBlock(Block $block, Block $prior = null)
    {
        // do nothing
    }
}