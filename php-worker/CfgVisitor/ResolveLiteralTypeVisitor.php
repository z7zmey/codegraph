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

class ResolveLiteralTypeVisitor implements Visitor
{

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
        foreach ($func->params as $param) {
            $this->processOp($param);
        }
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
        foreach ($op->getVariableNames() as $varName) {
            $vars = $op->$varName;

            if (is_array($vars)) {
                foreach ($vars as $key => $var) {
                    $this->processVar($var);
                }
            } else {
                $this->processVar($vars);
            }
        }
    }

    protected function processVar(Operand $var = null)
    {
        if (!$var instanceof Operand\Literal) {
            return;
        }
        
        $var->type = [gettype($var->value)];
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