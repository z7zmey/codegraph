import * as d3 from 'd3';

declare const dagreD3: any;

export const uml = function (parent, bbox, node) {
    var rowHeight = 30;
    var width = 0;
    var height = node['data']['Methods'].length * rowHeight + rowHeight;

    var g = parent.insert("g", ":first-child");

    var rectangles = g.selectAll('rect')
      .data(node['data']['Methods'])
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
      .data(node['data']['Methods'])
      .enter()
      .append("text")
      .attr('data-method', '')
      .text(function (d) {
        return d.Name
      })
      .style("cursor", "pointer");

    var headerText = g.insert("text").text(node['data']['Name']).style("font-weight", "bold");
    console.log(node['data']);
    if (node['data']['IsAbstract']) {
        headerText.style("font-style", "oblique")
    }

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