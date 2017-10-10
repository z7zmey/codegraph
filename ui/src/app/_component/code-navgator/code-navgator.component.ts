import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../_service/api.service';
import { Router, ActivatedRoute } from '@angular/router';
import { Response } from '@angular/http';
import * as d3 from 'd3';

declare const $: any;
declare const dagreD3: any;

@Component({
  selector: 'app-code-navgator',
  templateUrl: './code-navgator.component.html',
  styleUrls: ['./code-navgator.component.css'],
})
export class CodeNavgatorComponent implements OnInit {

  protected protectedId: number;
  protected query: string;
  protected found;

  protected code: string;

  constructor(private apiService: ApiService, private activatedRoute: ActivatedRoute) {
    this.protectedId = activatedRoute.snapshot.params['id'];
  }

  ngOnInit() {
    if (this.activatedRoute.snapshot.params['methodName']) {
      this.show(this.activatedRoute.snapshot.params['methodName'])
    }
  }

  search() {
    if (!this.query) {
      return;
    }

    this.apiService.searchMethod(this.query)
      .subscribe(
        (response: Response) => {
          if (response.status !== 200) {
            alert('Error! See log for details.');
            console.log('response', response);
          }

          this.found = response.json();
        },
      );
  }

  show(id: string) {
    this.apiService.getMethodCalls(id)
      .subscribe(
        (response: Response) => {
          if (response.status !== 200) {
            alert('Error! See log for details.');
            console.log('response', response);
          }

          this.showD3(response.json());
        },
      );

    this.query = '';
    this.found = null;
  }

  showD3(data) {
    const self = this;
    const g = new dagreD3.graphlib.Graph().setGraph({
      rankdir: 'LR',
      align: 'UL',
      nodesep: 10,
      // ranker: 'longest-path',
    });

    data.forEach(function (node) {
      g.setNode(node['id'], { label: node['name'], rx: 5, ry: 5, title: node['id'] });
    });

    data.forEach(function (node) {
      if (node['calls'] !== null) {
        node['calls'].forEach(function(callMethod) {
          g.setEdge(node['id'], callMethod, {});
        });
      }
      if (node['implementations'] !== null) {
        node['implementations'].forEach(function(methodImplementation) {
          g.setEdge(node['id'], methodImplementation, {
            style: "stroke-dasharray: 5, 5;",
          });
        });
      }
    });

    const render = new dagreD3.render();
    const svg = d3.select('svg');

    svg.select('g').remove();
    const inner = svg.append('g');

    render(inner, g);

    // const styleTooltip = function(namespacedName) {
    //   return '<p class=\'name\'>' + namespacedName + '</p>';
    // };
    // inner.selectAll('g.node')
      // .attr('title', function(v) { return styleTooltip(g.node(v).title); })
      // .each(function(v) { $(this).tooltip({ gravity: 'w', opacity: 1, html: true }); });


    inner.attr('transform', 'translate(20, 20)');
    svg.attr('height', g.graph().height + 40);
    svg.attr('width', g.graph().width + 40);

    svg.selectAll('g.node').on('click', function (id) {
      self.code = null;
      svg.selectAll('g.node rect').style('fill', null);
      d3.select(this).select('rect').style('fill', '#afa');

      self.apiService.getMethodCode(id)
        .subscribe(
          (response: Response) => {
            if (response.status !== 200) {
              alert('Error! See log for details.');
              console.log('response', response);
            }

            self.code = response.text();
          },
        );
    });
  }

}
