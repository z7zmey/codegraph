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
	"os"
	"path/filepath"
	"strings"
)

type PathNode struct {
	Path       string      `json:"id"`
	Name       string      `json:"name"`
	Children   []*PathNode `json:"children"`
	IsExpanded bool        `json:"isExpanded"`
	IsDir      bool        `json:"IsDir"`
}

type PathNodeHierarchy struct {
	Node     *PathNode
	Children map[string]PathNodeHierarchy
}

func GetPathTree(dir string) PathNode {
	rootNode := PathNode{dir, dir, []*PathNode{}, true, true}
	rootNodeH := PathNodeHierarchy{&rootNode, map[string]PathNodeHierarchy{}}

	visit := func(path string, info os.FileInfo, err error) error {
		if path == dir {
			return nil
		}

		shortPath := strings.TrimPrefix(path, dir)
		shortPath = strings.Trim(shortPath, string(filepath.Separator))
		segments := strings.Split(shortPath, string(filepath.Separator))

		nodeH := rootNodeH
		for _, segment := range segments {
			if _, ok := nodeH.Children[segment]; !ok {
				node := PathNode{path, segment, []*PathNode{}, false, info.IsDir()}
				nodeH.Children[segment] = PathNodeHierarchy{&node, map[string]PathNodeHierarchy{}}
				nodeH.Node.Children = append(nodeH.Node.Children, &node)
			}

			nodeH, _ = nodeH.Children[segment]
		}

		return nil
	}

	err := filepath.Walk(dir, visit)
	if err != nil {
		log.Fatal(err)
	}

	return rootNode
}
