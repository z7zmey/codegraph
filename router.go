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
	"strings"

	"github.com/yookoala/realpath"

	"github.com/gorilla/mux"
)

// NewRouter TODO
func NewRouter() *mux.Router {
	codeGraphDir, err := filepath.Abs(filepath.Dir(os.Args[0]))
	if err != nil {
		log.Fatal(err)
	}

	if _, err := os.Stat(codeGraphDir + "/ui"); os.IsNotExist(err) {
		codeGraphDir = "/usr/local/lib/codegraph"
	}

	router := mux.NewRouter() //.StrictSlash(true)

	router.PathPrefix("/app{_:.*}").Handler(wrapByLogger(StaticFileServer, "app"))

	for _, route := range routes {
		r := router.
			Methods(route.Method).
			Path(route.Pattern).
			Name(route.Name).
			Handler(wrapByLogger(route.HandlerFunc, route.Name))

		for key, val := range route.queries {
			r.Queries(key, val)
		}
	}

	return router
}

func wrapByLogger(f func(w http.ResponseWriter, r *http.Request), name string) http.Handler {
	var appHandler http.Handler
	appHandler = http.HandlerFunc(f)
	return Logger(appHandler, "app")
}

func StaticFileServer(w http.ResponseWriter, r *http.Request) {
	codeGraphDir, err := filepath.Abs(filepath.Dir(os.Args[0]))
	if err != nil {
		log.Fatal(err)
	}

	if _, err := os.Stat(codeGraphDir + "/ui"); os.IsNotExist(err) {
		codeGraphDir = "/usr/local/lib/codegraph"
	}

	path, err := realpath.Realpath(codeGraphDir + "/ui/dist/" + r.URL.Path)

	if err != nil || !strings.HasPrefix(path, codeGraphDir+"/ui/dist/app") {
		http.ServeFile(w, r, codeGraphDir+"/ui/dist/app/index.html")
		return
	}

	if _, err := os.Stat(path); os.IsNotExist(err) { // if target file not exist
		http.ServeFile(w, r, codeGraphDir+"/ui/dist/app/index.html")
	}

	http.ServeFile(w, r, path)
}
