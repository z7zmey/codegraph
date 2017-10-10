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

require_once __DIR__ . '/vendor/autoload.php';

$cmd = new Commando\Command();

$cmd->option('file')
    ->describedAs('file to process')
    ->must(function ($file) {
        return is_file($file);
    });

$cmd->option('port')
    ->describedAs('code socket socket port')
    ->map(function ($port) {
        return (int)$port;
    })
    ->must(function ($port) {
        return (bool)filter_var($port, FILTER_VALIDATE_INT);
    })
    ->default(3333);

$cmd->option('cfg')
    ->describedAs('process cfg')
    ->boolean();

$client = new Worker\Client('localhost', $cmd['port']);

$parserFactory = new \PhpParser\ParserFactory();
$parser = $parserFactory->create(\PhpParser\ParserFactory::PREFER_PHP7);

if (!$cmd['cfg']) {
    $astParser = new \Worker\AstParser($client, $parser);
    $astParser->processFileAst($cmd['file']);
} else {
    $parser = new \PHPCfg\Parser($parser);
    $cfgParser = new \Worker\CfgParser($client, $parser);
    $cfgParser->processFileCfg($cmd['file']);
}
