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

namespace Worker;

class Client
{
    private $host;
    
    private $port;
    
    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }
    
    public function sendMessage(array $data) {
        if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            die(sprintf('Unable to create a socket: %s', socket_strerror(socket_last_error())));
        }
    
        if (!@socket_connect($socket, $this->host, $this->port)) {
            die(sprintf('Unable to connect to server %s:%s: %s', $this->host, $this->port, socket_strerror(socket_last_error())));
        }
        
        if (socket_write($socket, json_encode($data)) === false) {
            die(sprintf("Unable to write to socket: %s", socket_strerror(socket_last_error())));
        }
        
        socket_close($socket);
    }
}