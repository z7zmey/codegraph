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
	"log"
	"net/http"
	"os"
	"path/filepath"

	"github.com/gorilla/mux"
)

// NewRouter TODO
func NewRouter() *mux.Router {
	codeGraphDir, err := filepath.Abs(filepath.Dir(os.Args[0]))
	if err != nil {
		log.Fatal(err)
	}

	if _, err := os.Stat(codeGraphDir+"/ui"); os.IsNotExist(err) {
		codeGraphDir = "/usr/local/lib/codegraph"
	}

	router := mux.NewRouter() //.StrictSlash(true)

	router.PathPrefix("/app").Handler(http.StripPrefix("/app", http.FileServer(http.Dir(codeGraphDir+"/ui/dist/app"))))

	for _, route := range routes {
		var handler http.Handler

		handler = route.HandlerFunc
		handler = Logger(handler, route.Name)

		r := router.
			Methods(route.Method).
			Path(route.Pattern).
			Name(route.Name).
			Handler(handler)

		for key, val := range route.queries {
			r.Queries(key, val)
		}
	}

	return router
}
