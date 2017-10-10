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

declare(strict_types=1);

namespace Worker;

use Worker\AstVisitor\ExtendResolver;
use PhpParser\NodeTraverser;
use PhpParser\Parser;

class AstParser
{
    private $client;
    
    public function __construct(\Worker\Client $client, Parser $parser)
    {
        $this->client = $client;
        $this->parser = $parser;
    }
    
    public function processFileAst($file)
    {
        $this->client->sendMessage(['files' => [
            ['path' => $file],
        ]]);
        
        $code = file_get_contents($file);
        $stmts = $this->parser->parse($code);
        
        $traverser = new NodeTraverser();
        $traverser->addVisitor($nameResolver = new \Worker\AstVisitor\NameResolver());
        $traverser->addVisitor(new ExtendResolver());
        $traverser->addVisitor(new \Worker\AstVisitor\ResolveReturnTypes($nameResolver));
        $traverser->addVisitor(new \Worker\AstVisitor\ResolvePropertyTypes($nameResolver));
        $traverser->addVisitor(new \Worker\AstVisitor\SetParentVisitor());
//        $traverser->addVisitor(new \Worker\AstVisitor\HashVisitor($file));
        $traverser->addVisitor(new \Worker\AstVisitor\GraphVisitor($this->client, $file));
        $traverser->traverse($stmts);
    }
}