import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../_service/api.service';
import { Response } from '@angular/http';
import { Router, ActivatedRoute } from '@angular/router';
import * as d3 from 'd3';

declare const $: any;
declare const dagreD3: any;

@Component({
  selector: 'app-uml',
  templateUrl: './uml.component.html',
  styleUrls: ['./uml.component.css']
})
export class UmlComponent implements OnInit {

  constructor(private apiService: ApiService, private router: Router, private activatedRoute: ActivatedRoute) {
    let render = new dagreD3.render();

    render.shapes().uml = function (parent, bbox, node) {
      var rowHeight = 30;
      var width = 0;
      var height = node.methods.length * rowHeight + rowHeight;

      var g = parent.insert("g", ":first-child");

      var rectangles = g.selectAll('rect')
        .data(node.methods)
        .enter()
        .append("rect")
        .attr('data-method', '')
        .style("cursor", "pointer");

      var headerRect = g.insert("rect", ":first-child").style("fill", "#eee");

      rectangles.attr("x", 0)
        .attr("y", function (d, k) {
          return rowHeight * k + rowHeight
        })
        .attr("height", rowHeight);

      headerRect.attr("x", 0)
        .attr("y", 0)
        .attr("height", rowHeight);

      var text = g.selectAll('text')
        .data(node.methods)
        .enter()
        .append("text")
        .attr('data-method', '')
        .text(function (d) {
          return d.Name
        })
        .style("cursor", "pointer");

      var headerText = g.insert("text").text(node['name']).style("font-weight", "bold");

      headerText.each(function () {
        var textWidth = this.getComputedTextLength();
        width = textWidth > width ? textWidth : width;
      });
      text.each(function () {
        var textWidth = this.getComputedTextLength();
        width = textWidth > width ? textWidth : width;
      });

      text.attr("x", 15)
        .attr("dy", "1em")
        .attr("y", function (d, k) {
          return (rowHeight * k + rowHeight) + ((rowHeight - this.getBBox().height) / 2)
        });

      headerText.attr("x", 15)
        .attr("dy", "1em")
        .attr("y", function (d, k) {
          return ((rowHeight - this.getBBox().height) / 2)
        });

      width += 30;

      rectangles.attr("width", width);
      headerRect.attr("width", width);
      g.attr("transform", "translate(" + (-width / 2) + "," + (-height / 2) + ")");

      parent.select('.label').remove();

      node.intersect = function (point) {
        return dagreD3.intersect.rect(node, point);
      };
      return g;
    };
  }

  ngOnInit() {
    this.activatedRoute.paramMap.subscribe(params => {
      if (params.get('path') !== null) {
        this.apiService.getUml(params.get('path'))
        .subscribe(
          (response: Response) => {
            if (response.status !== 200) {
              alert('Error! See log for details.');
              console.log('response', response);
            }

            this.showD3(response.json());
          },
        );
      }
    });


  }

  showD3(data) {
    const self = this;
    const g = new dagreD3.graphlib.Graph().setGraph({
      rankdir: 'BT',
      // align: 'UL',
      nodesep: 10,
      ranker: 'tight-tree',
    });

    var classes = {};
    data.forEach(function (node) {
      g.setNode(node['Name'], { shape: "uml", name: node['Name'], methods: node['Methods'] });
      classes[node['Name']] = node['Name'];
    });

    data.forEach(function (node) {
      if (node['Extends'] !== '' && classes[node['Extends']]) {
        g.setEdge(node['Name'], node['Extends'], {});
      }
      if (node['Implements'] !== null) {
        node['Implements'].forEach(function (classImplementation) {
          if (classes[classImplementation]) {
            g.setEdge(node['Name'], classImplementation, {style: "stroke-dasharray: 5, 5;"});
          }
        });
      }
    });

    const render = new dagreD3.render();
    const svg = d3.select('svg');

    svg.select('g').remove();
    const inner = svg.append('g').attr("transform", "scale(0.5)");


    render(inner, g);

    inner.attr('transform', 'translate(20, 20)');
    svg.attr('height', g.graph().height + 40);
    svg.attr('width', g.graph().width + 40);

    svg.selectAll('g.node rect[data-method], g.node text[data-method]').on('click', function (data) {
      self.router.navigate(['/codeNavigator/', { methodName: data['NsName'] }]);
    });
  }
}
