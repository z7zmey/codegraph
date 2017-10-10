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

class CfgVisitor implements Visitor
{
    /**
     * @var \SplObjectStorage
     */
    protected $blocks;
    /**
     * @var \SplObjectStorage
     */
    protected $vars;
    /**
     * @var \SplObjectStorage
     */
    protected $ops;
    protected $graph;
    protected $scriptVId;
    protected $funcVId;
    protected $blockVId;
    protected $parentVId;
    
    public function __construct(Graph $graph)
    {
        $this->graph = $graph;
    }
    
    public function enterScript(Script $script)
    {
        $data = $this->getData($script);
        
        $this->scriptVId = $script->vId = $vId = $this->graph->createVertex('Cfg', $data);
        $this->graph->createEdge(
            'Cfg_Script_Edge',
            'file',
            $vId
        );
    }
    
    public function leaveScript(Script $script)
    {
    }
    
    public function enterBlock(Block $block, Block $prior = null)
    {
        $blockId = $this->getBlockId($block);
        
        $data = $this->getData($block, ['id' => $blockId]);
        
        $this->parentVId = $this->blockVId = $block->vId = $vId = $this->graph->createVertex('Cfg_Block', $data);
        
        $this->graph->createEdge(
            'Cfg_Block_Edge',
            $this->funcVId,
            $vId
        );
        
        foreach ($block->phi as $phi) {
            $this->processOp($phi, $block);
//            $this->processVar($block, $phi->result, ['name' => 'phi']);
        }
    }
    
    public function leaveBlock(Block $block, Block $prior = null)
    {
    }
    
    public function enterOp(Op $op, Block $block)
    {
        $this->processOp($op, $block);
        
        $this->graph->createEdge(
            'Cfg_Ops',
            $this->parentVId,
            $op->vId
        );
    }
    
    public function leaveOp(Op $op, Block $block)
    {
        $this->parentVId = $op->vId;
    }
    
    public function skipBlock(Block $block, Block $prior = null)
    {
        
    }
    
    public function enterFunc(Func $func)
    {
        $this->blocks = new \SplObjectStorage;
        $this->vars = new \SplObjectStorage;
        $this->ops = new \SplObjectStorage;
        $scopedName = $func->getScopedName();
        
        $data = $this->getData($func, ['scoped_name' => $func->getScopedName()]);
        
        $this->funcVId = $func->vId = $vId = $this->graph->createVertex('Cfg_Func', $data);
        
        $this->graph->createEdge(
            'Cfg_Func_Edge',
            $this->scriptVId,
            $vId
        );
        
        foreach ($func->params as $param) {
            $this->processOp($param, $func);
        }
    }
    
    public function leaveFunc(Func $func)
    {
        if ($func->callableOp) {
            $this->graph->createEdge(
                'Cfg_Callable_Op',
                $func->vId,
                $func->callableOp->vId
            );
        }
        
        /** @var Block $block */
        foreach ($this->blocks as $block) {
            if (!$block->record) {
                continue;
            }
            
            foreach ($block->parents as $parent) {
                if (!$parent->record) {
                    continue;
                }
                $this->graph->createEdge(
                    'Cfg_Child_Block',
                    $parent->vId,
                    $block->vId
                );
            }

//            foreach ($block->phi as $phi) {
//                foreach ($phi->vars as $var) {
//                    if (!$var->record) {
//                        $this->processVar($block, $var, ['type' => 'phi_tmp']);
//                    }
//                    
//                    $this->client->command(sprintf(
//                        'create edge Cfg_Phi_Var from %s to %s',
//                        $phi->result->record->getRid(),
//                        $var->record->getRid()
//                    ));
//                }
//            }
        }
        
        foreach ($this->vars as $var) {
            if (!isset($var->ops)) {
                continue;
            }
            
            foreach ($var->ops as $op) {
                if (!$var->record || !$op->record) {
                    continue;
                }
                $this->graph->createEdge(
                    'Cfg_Var_Ops',
                    $var->vId,
                    $op->vId
                );
            }
            
            foreach ($var->usages as $op) {
                if (!$var->record || !$op->record) {
                    continue;
                }
                $this->graph->createEdge(
                    'Cfg_Var_Usages',
                    $var->vId,
                    $op->vId
                );
            }
        }
        
        /** @var Op $op */
        foreach ($this->ops as $op) {
            foreach ($op->getSubBlocks() as $blockName) {
                $sub = $op->$blockName;
                if (is_array($sub)) {
                    foreach ($sub as $key => $subBlock) {
                        if (!$subBlock) {
                            continue;
                        }
                        
                        $this->graph->createEdge(
                            'Cfg_Sub_Block_Edge',
                            $op->vId,
                            $subBlock->vId,
                            ['blockName' => $blockName]
                        );
                    }
                } elseif ($sub && $sub->record) { // TODO: разобраться почему траверсер не обрабатывает default блок для параметров функции
                    $this->graph->createEdge(
                        'Cfg_Sub_Block_Edge',
                        $op->vId,
                        $sub->vId,
                        ['blockName' => $blockName]
                    );
                }
            }
        }
        
        $this->blocks = null;
        $this->vars = null;
        $this->ops = null;
    }
    
    // Helper functions
    
    protected function getBlockId(Block $block)
    {
        if (!$this->blocks->contains($block)) {
            $this->blocks[$block] = count($this->blocks) + 1;
        }
        return $this->blocks[$block];
    }
    
    protected function getVarId(Operand $var)
    {
        if (!$this->vars->contains($var)) {
            $this->vars[$var] = count($this->vars) + 1;
        }
        return $this->vars[$var];
    }
    
    protected function getOpId(Op $op)
    {
        if (!$this->ops->contains($op)) {
            $this->ops[$op] = count($this->ops) + 1;
        }
        return $this->ops[$op];
    }
    
    protected function getData($obj, array $params = [])
    {
        $data = $params;
        foreach ($obj as $key => $value) {
            if (is_scalar($value)) {
                $data[$key] = $value;
            }
            
            if (is_array($value)) {
                $value = array_filter($value, function ($var) {
                    return is_scalar($var);
                });
                if ($value) {
                    $data[$key] = $value;
                }
            }
            
            if ($value instanceof Operand\Literal) {
                $value = $value->value;
                if ($value) {
                    $data[$key] = $value;
                }
            }
        }
        
        return $data;
    }
    
    protected function processOp(Op $op, $parent)
    {
        $data = [
            'type' => $op->getType(),
            'id' => $this->getOpId($op),
        ];
        
        $class = 'Cfg_' . $op->getType();
        
        $op->vId = $vId = $this->graph->createVertex($class, $data);
        
        $this->graph->createEdge(
            "Cfg_Operation_Edge",
            $parent->vId,
            $vId
        );
        
        foreach ($op->getVariableNames() as $varName) {
            $vars = $op->$varName;
            
            if (is_array($vars)) {
                foreach ($vars as $key => $var) {
                    $this->processVar($op, $var, ['name' => $varName, 'key' => $key]);
                }
            } else {
                $this->processVar($op, $vars, ['name' => $varName]);
            }
        }
    }
    
    protected function processVar($parent, Operand $var = null, array $edgeData = [])
    {
        if ($var === null) {
            return;
        }
        
        $data = [
            'id' => $this->getVarId($var),
            'variable_type' => get_class($var),
        ];
        
        if (isset($var->name)) {
            $data['name'] = $var->name->value;
        }
        
        if (isset($var->type)) {
            $data['type'] = $var->type->value;
        }
        
        if (!isset($var->vId)) {
            $var->vId = $this->graph->createVertex($this->getVariableCls($var), $this->getData($var, $data));
        }
        
        if (isset($var->original)) {
            $this->processVar($var, $var->original, ['name' => 'original']);
        }
        
        $this->graph->createEdge(
            'Cfg_Var_Edge',
            $parent->vId,
            $var->vId,
            $edgeData
        );
    }
    
    protected function getVariableCls(Operand $var)
    {
        if ($var instanceof Operand\BoundVariable) {
            return 'Cfg_Bound_Var';
        }
        if ($var instanceof Operand\Literal) {
            return 'Cfg_Literal';
        }
        if ($var instanceof Operand\Temporary) {
            return 'Cfg_Temp';
        }
        if ($var instanceof Operand\Variable) {
            return 'Cfg_Variable';
        }
        
        throw new \Exception('Unknown operand');
    }
}