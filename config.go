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
	"flag"
	"strings"
)

type ArrayFlags []string

func (i *ArrayFlags) String() string {
	return strings.Join(*i, " ")
}

func (i *ArrayFlags) Set(value string) error {
	*i = append(*i, value)
	return nil
}

type CGConfig struct {
	path ArrayFlags
	exclude ArrayFlags
	debug bool
}

var Config = CGConfig{}

func ParseConfigFlags() {
	flag.Var(&Config.path, "path", "path to sources")
	flag.Var(&Config.path, "p", "path to sources (shorthand)")

	flag.Var(&Config.exclude, "exclude", "exclude path")
	flag.Var(&Config.exclude, "e", "exclude path (shorthand)")

	flag.BoolVar(&Config.debug, "debug", false, "print debug info")
	flag.BoolVar(&Config.debug, "d", false, "print debug info (shorthand)")

	flag.Parse()
}
