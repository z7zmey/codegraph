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
	"os/exec"
	"path/filepath"
	"sync"

	"github.com/cayleygraph/cayley"
	"github.com/cayleygraph/cayley/graph"
	"github.com/cayleygraph/cayley/quad"
	"github.com/cayleygraph/cayley/schema"
)

func ProcessPath(dir string) {
	codeGraphDir, err := filepath.Abs(filepath.Dir(os.Args[0]))

	if _, err := os.Stat(codeGraphDir+"/php-worker"); os.IsNotExist(err) {
		codeGraphDir = "/usr/local/lib/codegraph"
	}

	if err != nil {
		log.Fatal(err)
	}

	var wg sync.WaitGroup

	var astFileChan = make(chan string)
	go processFileAst(codeGraphDir, astFileChan, &wg)
	go processFileAst(codeGraphDir, astFileChan, &wg)
	go processFileAst(codeGraphDir, astFileChan, &wg)
	go processFileAst(codeGraphDir, astFileChan, &wg)

	err = filepath.Walk(dir, func(path string, f os.FileInfo, err error) error {
		if !f.IsDir() && filepath.Ext(path) == ".php" {
			wg.Add(1)
			astFileChan <- path
		}
		return nil
	})
	if err != nil {
		log.Fatal(err)
	}

	wg.Wait()

	go setMethodsImplementations()

	var cfgFileChan = make(chan string)
	go processFileCfg(codeGraphDir, cfgFileChan)
	go processFileCfg(codeGraphDir, cfgFileChan)
	go processFileCfg(codeGraphDir, cfgFileChan)
	go processFileCfg(codeGraphDir, cfgFileChan)

	err = filepath.Walk(dir, func(path string, f os.FileInfo, err error) error {
		if !f.IsDir() && filepath.Ext(path) == ".php" {
			cfgFileChan <- path
		}
		return nil
	})
	if err != nil {
		log.Fatal(err)
	}
}

func processFileAst(codeGraphDir string, files chan string, wg *sync.WaitGroup) {
	for file := range files {
		cmd := exec.Command("php", codeGraphDir+"/php-worker/worker.php", "--file", file)
		cmd.Stdout = os.Stdout
		cmd.Stderr = os.Stderr
		cmd.Run()
		cmd.Wait()
		wg.Done()
	}
}

func processFileCfg(codeGraphDir string, files chan string) {
	for file := range files {
		cmd := exec.Command("php", codeGraphDir+"/php-worker/worker.php", "--file", file, "--cfg")
		cmd.Stdout = os.Stdout
		cmd.Stderr = os.Stderr
		cmd.Run()
	}
}

func setMethodsImplementations() {
	p := cayley.StartPath(store).Has(quad.IRI("ast:is_abstract"), quad.Bool(true))

	var methods []AstMethod
	err := schema.LoadPathTo(nil, store, &methods, p)
	checkErr(err)

	for _, method := range methods {
		setMethodImplementations(method)
	}
}

func setMethodImplementations(method AstMethod) {
	follow := cayley.StartPath(store).In(quad.IRI("ast:extends"), quad.IRI("ast:implements"))

	p := cayley.StartPath(store, quad.IRI(method.ID)).
		Has(quad.IRI("ast:is_abstract"), quad.Bool(true)).
		Out(quad.IRI("ast:class")).
		FollowRecursive(follow, []string{}).
		In(quad.IRI("ast:class")).
		Has(quad.IRI("ast:name"), quad.String("register")).
		Except(cayley.StartPath(store).Has(quad.IRI("ast:is_abstract"), quad.Bool(true)))

	var implementations []AstMethod
	err := schema.LoadPathTo(nil, store, &implementations, p)
	checkErr(err)

	method.Implementations = []quad.IRI{}
	for _, implementation := range implementations {
		method.Implementations = append(method.Implementations, quad.IRI(implementation.ID))
	}

	qw := graph.NewWriter(store)
	id, err := schema.WriteAsQuads(qw, method)
	checkErr(err)
	fmt.Printf("saving abstract method implementations %s: %+v\n", id, method)

	err = qw.Flush()
	checkErr(err)

	err = qw.Close()
	checkErr(err)
}
