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
	"log"
	"os"
	"os/signal"
	"runtime"
	"syscall"

	"github.com/cayleygraph/cayley"
)

var store *cayley.Handle

func main() {
	// разобраться с ошибкой fatal error: concurrent map writes
	runtime.GOMAXPROCS(1)

	var err error
	store, err = cayley.NewMemoryGraph()
	checkErr(err)
	defer store.Close()

	sigs := make(chan os.Signal, 1)

	signal.Notify(sigs, syscall.SIGINT, syscall.SIGTERM)

	go ListenAndServeAPI()
	go ListenAndServeSocket()

	dir, err := os.Getwd()
	if err != nil {
		log.Fatal(err)
	}

	// TODO: get a dir from cli
	ProcessPath(dir)

	<-sigs
	fmt.Println()
	fmt.Println("exiting")
}
