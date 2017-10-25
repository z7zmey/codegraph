import { Component, OnInit } from '@angular/core';
import { ApiService } from '../../_service/api.service';
import { Response } from '@angular/http';
import { Router, ActivatedRoute } from '@angular/router';
import { uml } from '../../shapes.uml';
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

    render.shapes().uml = uml;
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
      g.setNode(node['Name'], { shape: "uml", data: node });
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
