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

	"github.com/cayleygraph/cayley/schema"
)

var jsonData []byte

func CacheClear() {
	jsonData = nil
}

func CacheGetAst() []byte {
	if jsonData == nil {
		prepareJsonData()
	}

	return jsonData
}

type cacheClass struct {
	Name       string   `json:"name" `
	Extends    string   `json:"extends"`
	Implements []string `json:"implements"`
}

type cacheMethod struct {
	ID    string   `json:"id"`
	Types []string `json:"types"`
}

type cacheProperty struct {
	Name  string   `json:"name"`
	Types []string `json:"types"`
}

type astCache struct {
	Classes    []cacheClass
	Methods    []cacheMethod
	Properties []cacheProperty
}

func prepareJsonData() {
	var astClasses []AstClass
	err := schema.LoadTo(nil, store, &astClasses)
	checkErr(err)

	var astMethods []AstMethod
	err = schema.LoadTo(nil, store, &astMethods)
	checkErr(err)

	var astProps []AstProperty
	err = schema.LoadTo(nil, store, &astProps)
	checkErr(err)

	var cache astCache
	cache.Classes = []cacheClass{}
	cache.Methods = []cacheMethod{}
	cache.Properties = []cacheProperty{}

	for _, astClass := range astClasses {
		var cacheClass cacheClass
		cacheClass.Name = astClass.Name
		cacheClass.Extends = string(astClass.Extends)
		cacheClass.Implements = []string{}

		for _, implements := range astClass.Implements {
			cacheClass.Implements = append(cacheClass.Implements, string(implements))
		}

		cache.Classes = append(cache.Classes, cacheClass)
	}

	for _, astMethod := range astMethods {
		var cacheMethod cacheMethod
		cacheMethod.ID = astMethod.ID
		cacheMethod.Types = []string{}

		for _, methodType := range astMethod.Types {
			cacheMethod.Types = append(cacheMethod.Types, string(methodType))
		}

		cache.Methods = append(cache.Methods, cacheMethod)
	}

	for _, astProperty := range astProps {
		var cacheProperty cacheProperty
		cacheProperty.Name = astProperty.Name
		cacheProperty.Types = []string{}

		for _, proprtyType := range astProperty.Types {
			cacheProperty.Types = append(cacheProperty.Types, string(proprtyType))
		}

		cache.Properties = append(cache.Properties, cacheProperty)
	}

	jsonData, err = json.Marshal(cache)
	checkErr(err)
}
