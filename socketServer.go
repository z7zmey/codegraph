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
	"encoding/json"
	"fmt"
	"log"
	"net"
	"os"

	"github.com/cayleygraph/cayley/graph"
	"github.com/cayleygraph/cayley/schema"

	_ "github.com/cayleygraph/cayley/voc/core"
)

const (
	connHost = "localhost"
	connPort = "3333"
	connType = "tcp"
)

// Message TODO
type Message struct {
	Files      []AstFile      `json:"files"`
	Classes    []AstClass     `json:"classes"`
	Methods    []AstMethod    `json:"methods"`
	Properties []AstProperty  `json:"properties"`
}

func checkErr(err error) {
	if err != nil {
		log.Fatal(err)
	}
}

// ListenAndServeSocket TODO
func ListenAndServeSocket() {
	// Listen for incoming connections.
	l, err := net.Listen(connType, connHost+":"+connPort)
	if err != nil {
		fmt.Println("Error listening:", err.Error())
		os.Exit(1)
	}
	// Close the listener when the application closes.
	defer l.Close()

	connections := make(chan net.Conn)
	go handleRequest(connections)

	fmt.Println("Listening socket on " + connHost + ":" + connPort)
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
func handleRequest(connections chan net.Conn) {
	for conn := range connections {

		clearAstCache := make([]byte, 1)
		_, err := conn.Read(clearAstCache)
		checkErr(err)

		d := json.NewDecoder(conn)

		var msg Message
		err = d.Decode(&msg)
		if err != nil {
			fmt.Println(err)
		}
		handleMessage(msg)

		conn.Close()

		if (clearAstCache[0] == '1') {
			CacheClear()
		}
	}
}

func handleMessage(msg Message) {
	if Config.debug {
		fmt.Printf("process message: %+v\n", msg)
	}

	qw := graph.NewWriter(store)

	for _, astFile := range msg.Files {
		var id, err = schema.WriteAsQuads(qw, astFile)
		checkErr(err)

		if Config.debug {
			fmt.Printf("saving %s: %+v\n", id, astFile)
		}
	}

	for _, AstClass := range msg.Classes {
		var id, err = schema.WriteAsQuads(qw, AstClass)
		checkErr(err)

		if Config.debug {
			fmt.Printf("saving %s: %+v\n", id, AstClass)
		}
	}

	for _, astMethod := range msg.Methods {
		var id, err = schema.WriteAsQuads(qw, astMethod)
		checkErr(err)

		if Config.debug {
			fmt.Printf("saving %s: %+v\n", id, astMethod)
		}
	}

	for _, astProperty := range msg.Properties {
		var id, err = schema.WriteAsQuads(qw, astProperty)
		checkErr(err)

		if Config.debug {
			fmt.Printf("saving %s: %+v\n", id, astProperty)
		}
	}

	err := qw.Close()
	checkErr(err)
}
