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

import "net/http"

type Route struct {
	Name        string
	Method      string
	Pattern     string
	HandlerFunc http.HandlerFunc
	queries     map[string]string
}

type Routes []Route

var routes = Routes{
	Route{
		"Index",
		"GET",
		"/",
		Index,
		map[string]string{},
	},

	// API

	Route{
		"getAll",
		"GET",
		"/api/all",
		GetAll,
		map[string]string{},
	},

	Route{
		"getTree",
		"GET",
		"/api/tree",
		GetTree,
		map[string]string{},
	},

	Route{
		"getUml",
		"GET",
		"/api/uml",
		GetUml,
		map[string]string{"path": "{path}"},
	},
	Route{
		"getClasses",
		"GET",
		"/api/classes",
		GetClasses,
		map[string]string{},
	},
	Route{
		"getMethods",
		"GET",
		"/api/methods",
		GetMethods,
		map[string]string{},
	},
	Route{
		"getMethodCalls",
		"GET",
		"/api/method/calls",
		GetMethodCalls,
		map[string]string{
			"name": "{name}",
		},
	},
	Route{
		"getMethodCode",
		"GET",
		"/api/method/code",
		GetMethodCode,
		map[string]string{
			"name": "{name}",
		},
	},
	Route{
		"getAstData",
		"GET",
		"/api/ast",
		GetAstData,
		map[string]string{},
	},
	Route{
		"getAbstractMethods",
		"GET",
		"/api/methods/abstract",
		GetAbstractMethods,
		map[string]string{},
	},
	Route{
		"getProperties",
		"GET",
		"/api/properties",
		GetProperties,
		map[string]string{},
	},
}
