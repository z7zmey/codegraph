import * as d3 from 'd3';

declare const dagreD3: any;

export const uml = function (parent, bbox, node) {
    var rowHeight = 28;
    var width = 0;
    var height = node['data']['Methods'].length * rowHeight + rowHeight;

    var g = parent.insert("g", ":first-child");

    // greate header row
    const header = g.insert("g");
    createRow(header, rowHeight, node['data']['Name']);
    header.selectAll('rect').style("fill", "#eee").attr("height", rowHeight)
    header.selectAll('text').style("font-weight", "bold")

    if (node['data']['IsAbstract']) {
        header.selectAll('text').style("font-style", "oblique")
    }

    // create method rows
    for (let i = 0; i < node['data']['Methods'].length; i++) {
        let k = i + 1;
        let method = node['data']['Methods'][i]
        const row = g.insert("g").attr('data-method', '')
            .attr("transform", "translate(0, " + (rowHeight * k) +")");
        row.datum(method);
        createRow(row, rowHeight, method.Visibility + ' ' + method.Name);
    }

    // calc width
    width = header.selectAll('text').node().getComputedTextLength();
    g.selectAll('text').each(function () {
      var textWidth = this.getComputedTextLength();
      width = textWidth > width ? textWidth : width;
    });
    width += 30;
    g.selectAll('rect').attr("width", width);

    // centralize shape
    g.attr("transform", "translate(" + (-width / 2) + "," + (-height / 2) + ")");

    parent.select('.label').remove();

    node.intersect = function (point) {
      return dagreD3.intersect.rect(node, point);
    };
    return g;
  };

function createRow(g, height, text) {
    g.insert("rect")
        .attr("x", 0)
        .attr("y", 0)
        .attr("height", height);

    g.insert("text")
        .attr("x", 15)
        .attr("y", 14)
        .attr("dy", 1)
        .attr("alignment-baseline", "middle")
        .text(text);
}