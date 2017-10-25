/*
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

package main

import (
	"fmt"
	"net"
	"os"
	"time"

	_ "github.com/cayleygraph/cayley/voc/core"
)

const (
	connHostAst = "localhost"
	connPortAst = "3334"
	connTypeAst = "tcp"
)

// ListenAndServeSocket TODO
func ListenAndServeAstSocket() {
	// Listen for incoming connections.
	l, err := net.Listen(connTypeAst, connHostAst+":"+connPortAst)
	if err != nil {
		fmt.Println("Error listening:", err.Error())
		os.Exit(1)
	}
	// Close the listener when the application closes.
	defer l.Close()

	connections := make(chan net.Conn)
	go handleAstRequest(connections)

	fmt.Println("Listening ast socket on " + connHostAst + ":" + connPortAst)
	for {
		// Listen for an incoming connection.
		conn, err := l.Accept()
		if err != nil {
			fmt.Println("Error accepting: ", err.Error())
			os.Exit(1)
		}
		// Handle connections
		connections <- conn
	}
}

// Handles incoming requests.
func handleAstRequest(connections chan net.Conn) {
	for conn := range connections {
		t1 := time.Now()
		conn.Write(CacheGetAst())
		t2 := time.Now()

		if Config.debug {
			fmt.Printf("socket get ast: %s\n", t2.Sub(t1))
		}

		conn.Close()
	}
}
