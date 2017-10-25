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
	"github.com/cayleygraph/cayley/quad"
	"github.com/cayleygraph/cayley/schema"
	"github.com/cayleygraph/cayley/voc"
	_ "github.com/cayleygraph/cayley/voc/core"
)

type AstFile struct {
	rdfType struct{} `quad:"@type > ast:file"`
	Path    string   `json:"path" quad:"@id"`
}

type AstClass struct {
	rdfType     struct{}   `quad:"@type > ast:class"`
	Name        string     `json:"name" quad:"@id"`
	StartLine   int        `json:"startLine" quad:"ast:start_line,optional"`
	EndLine     int        `json:"endLine" quad:"ast:end_line,optional"`
	File        quad.IRI   `json:"file" quad:"ast:file"`
	Extends     quad.IRI   `json:"extends" quad:"ast:extends,optional"`
	Implements  []quad.IRI `json:"implements" quad:"ast:implements,optional"`
	IsAbstract  bool       `json:"isAbstract" quad:"ast:is_abstract,optional"`
	IsInterface bool       `json:"isInterface" quad:"ast:is_interface,optional"`
}

type AstMethod struct {
	rdfType         struct{}   `quad:"@type > ast:method"`
	ID              string     `json:"id" quad:"@id"`
	Name            string     `json:"name" quad:"ast:name,optional"`
	StartLine       int        `json:"startLine" quad:"ast:start_line,optional"`
	EndLine         int        `json:"endLine" quad:"ast:end_line,optional"`
	Class           quad.IRI   `json:"class" quad:"ast:class,optional"`
	Types           []quad.IRI `json:"types" quad:"ast:type,optional"`
	Calls           []quad.IRI `json:"calls" quad:"ast:calls,optional"`
	IsAbstract      bool       `json:"isAbstract" quad:"ast:is_abstract,optional"`
	Implementations []quad.IRI `json:"implementations" quad:"ast:method_implementation,optional"`
	Visibility      string     `json:"visibility" quad:"ast:method_visibility,optional"`
}

type AstProperty struct {
	rdfType   struct{}   `quad:"@type > ast:property"`
	Name      string     `json:"name" quad:"@id"`
	StartLine int        `json:"startLine" quad:"ast:start_line,optional"`
	EndLine   int        `json:"endLine" quad:"ast:end_line,optional"`
	Class     quad.IRI   `json:"class" quad:"ast:class"`
	Types     []quad.IRI `json:"types" quad:"ast:type"`
}

func InitSchema() {
	voc.RegisterPrefix("ast:", "http://z7zmey.com.ua/ast")

	schema.RegisterType(quad.IRI("ast:file"), AstFile{})
	schema.RegisterType(quad.IRI("ast:class"), AstClass{})
	schema.RegisterType(quad.IRI("ast:method"), AstMethod{})
	schema.RegisterType(quad.IRI("ast:property"), AstProperty{})
}
