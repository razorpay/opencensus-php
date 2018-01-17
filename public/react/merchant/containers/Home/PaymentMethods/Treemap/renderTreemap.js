import {
  titleCase,
  getFormattedAmount,
  paiseToRupees,
  arrayToCsvDataUrl,
} from 'rzp/utils/rzp-utils';
import { humanReadableIndianCurrency } from 'rzp/utils/numerals';
import {default as chartColors} from "rzp/utils/chart/colors";

import {
  paymentMethodsColumns
} from 'merchant/containers/Home/PaymentMethods/data';

var defaults = {
  margin: { top: 0, right: 0, bottom: 0, left: 0 },
  rootname: 'TOP',
  format: ',.2f',
  title: '',
  width: 500,
  height: 300 - 24, //leaving 24px at the bottom
};

function main(
  node,
  o,
  data,
  d3,
  onTransition,
  onShowTooltip,
  onHideTooltip,
  groupTitleMap
) {
  var root,
    opts = { ...defaults, ...o },
    formatNumber = getFormattedAmount,
    rname = opts.rootname,
    margin = opts.margin;

  node.style.width = opts.width + 'px';
  node.style.height = opts.height + 'px';
  node.style.position = 'realtive';

  var width = opts.width - margin.left - margin.right,
    height = opts.height - margin.top - margin.bottom,
    transitioning;

  var x = d3.scale
    .linear()
    .domain([0, width])
    .range([0, width]);

  var y = d3.scale
    .linear()
    .domain([0, height])
    .range([0, height]);

  var treemap = d3.layout
    .treemap()
    .children(function(d, depth) {
      return depth ? null : d._children;
    })
    .sort(function(a, b) {
      return a.value - b.value;
    })
    .size([1, 1])
    .round(false);

  var svg = d3
    .select(node)
    .append('svg')
    .attr('width', width + margin.left + margin.right)
    .attr('height', height + margin.bottom + margin.top)
    .style('margin-left', -margin.left + 'px')
    .style('margin.right', -margin.right + 'px')
    .append('g')
    .attr('transform', 'translate(' + margin.left + ',' + margin.top + ')')
    .style('shape-rendering', 'crispEdges');

  if (data instanceof Array) {
    root = { key: rname, values: data };
  } else {
    root = data;
  }

  var g1;

  var colors = {},
      aliases = {"emi": "card"};

  initialize(root);
  accumulate(root);

  /* 
   * Populating chart colors based on the values
   * Bigger the values get first colors in the
   * color palette
   */
  ([...root.values]).sort((item1, item2) => {
  
    return item2.value - item1.value;
  }).forEach((item, index) => {
 
    colors[item.key] = chartColors[index];
  });

  Object.keys(aliases).forEach((key) => {
  
    colors[key] = colors[aliases[key]];
  });

  layout(root);

  var transitionSubscriber = null,
      maxFontSize = 24;

  var globalTransition = display(root).transition;

  if (typeof onTransition === 'function') {
    onTransition(root, true);
  }

  function initialize(root) {
    root.x = root.y = 0;
    root.dx = width;
    root.dy = height;
    root.depth = 0;
  }

  function rollup(node, color) {
    node.color = color || colors[node.method] || 'black';

    if (node.depth === 1 && node.parent) {
      if (!node.percent) {
        const sum = node.parent._children.reduce(
          (result, child) => result + child.value,
          0
        );

        node.percent = (node.value / sum * 100).toFixed(2);
      }

      rollup(node.parent, node.color);
    }
  }

  // Aggregate the values for internal nodes. This is normally done by the
  // treemap layout, but not here because of our custom implementation.
  // We also take a snapshot of the original children (_children) to avoid
  // the children being overwritten when when layout is computed.
  function accumulate(d) {
    d.displayText = '';

    if (d.key) {
      d.displayText = groupTitleMap[d.key] || titleCase(d.key);
    }

    return (d._children = d.values)
      ? (d.value = d.values.reduce(function(p, v) {
          return p + accumulate(v);
        }, 0))
      : d.value;
  }

  // Compute the treemap layout recursively such that each group of siblings
  // uses the same size (1×1) rather than the dimensions of the parent cell.
  // This optimizes the layout for the current zoom state. Note that a wrapper
  // object is created for the parent node for each group of siblings so that
  // the parent’s dimensions are not discarded as we recurse. Since each group
  // of sibling was laid out in 1×1, we must rescale to fit using absolute
  // coordinates. This lets us use a viewport to zoom.
  function layout(d) {
    if (d._children) {
      treemap.nodes({ _children: d._children });
      d._children.forEach(function(c) {
        c.x = d.x + c.x * d.dx;
        c.y = d.y + c.y * d.dy;
        c.dx *= d.dx;
        c.dy *= d.dy;
        c.parent = d;
        layout(c);
      });
    } else {
      rollup(d);
    }
  }

  function canBeZoomed(d) {
    return (
      !d._children.length === 1 || typeof d._children[0].key !== 'undefined'
    );
  }

  function display(d, isTransitioning) {
    g1 = svg
      .append('g')
      .datum(d)
      .attr('class', 'depth');

    var g = g1
      .selectAll('g')
      .data(d._children)
      .enter()
      .append('g');

    g
      .filter(function(d) {
        return d.key && d._children;
      })
      .classed('children', true)
      .style('cursor', function(d) {
        return canBeZoomed(d) ? 'pointer' : 'default';
      })
      .style('font-size', d => {
        return Math.min((y(d.y + d.dy) - y(d.y)) * 0.2, maxFontSize) + 'px';
      })
      .on('mouseenter', function(d) {
        onShowTooltip({
          amount: d.value,
          percent: d.percent,
          label: d.displayText,
        });

        if (canBeZoomed(d)) {

          d3.select(this)
            .selectAll('rect.parent')
            .style('fill-opacity', 0.10);
        }
      })
      .on('mouseleave', function(d) {
     
        if (canBeZoomed(d)) {

          d3.select(this)
            .selectAll('rect.parent')
            .style('fill-opacity', 0);
        }
      })
      .on('click', function(d) {
        if (canBeZoomed(d) && typeof onTransition === 'function') {

          onTransition(d);
        }
      });

    var children = g
      .selectAll('.child')
      .data(function(d) {
        return d._children || [d];
      })
      .enter()
      .append('g');

    children
      .append('rect')
      .attr('class', 'child')
      .call(rect);

    g
      .append('rect')
      .attr('class', 'parent')
      .call(rect);

    var t = g
      .append('text')
      .attr('dx', '1em')
      .attr('dy', '0.75em')
      .attr('class', 'ptext')
      .style('font-size', '1em');

    t
      .append('tspan')
      .attr('class', 'amount method-text')
      .style('font-size', '1em')
      .attr('dx', '1em')
      .text(function(d) {
        return humanReadableIndianCurrency(paiseToRupees(d.value));
      })
      .append('tspan')
      .attr('class', 'amount-percent')
      .attr('dx', 6)
      .style('font-size', '0.6em')
      .style('fill', '#ffffff')
      .style('fill-opacity', 0.6)
      .text(function(d) {
        return `(${d.percent}%)`;
      });

    t
      .append('tspan')
      .style('font-size', '0.6em')
      .attr('dx', '1.67em') // inverse of 0.6
      .attr('dy', '1.5em')
      .attr('class', 'group-name method-text')
      .text(function(d) {
        return d.displayText;
      });

    t.call(text);

    g.selectAll('rect.child').style('fill', function(d) {
      return d.color;
    });

    function transition(d, inboundElements) {
      if (transitioning || !d) {
        transitionSubscriber = () => transition(d);
        return;
      }

      transitioning = true;

      let oldElements = svg.selectAll('g');

      var oldG1 = g1;

      var g2 = display(d).g,
        t1 = oldG1
          .transition()
          .duration(100)
          .ease('expOut'),
        t2 = g2
          .transition()
          .duration(100)
          .ease('expOut');

      // Update the domain only after entering new elements.
      x.domain([d.x, d.x + d.dx]);
      y.domain([d.y, d.y + d.dy]);

      // Enable anti-aliasing during the transition.
      svg.style('shape-rendering', null);

      // Draw child nodes on top of parent nodes.
      svg.selectAll('.depth').sort(function(a, b) {
        return a.depth - b.depth;
      });

      // Fade-in entering text.
      g2.selectAll('text').style('fill-opacity', 0);

      // Transition to the new view.
      t1
        .selectAll('.ptext')
        .call(text)
        .style('fill-opacity', 0);

      t2
        .each('end', function(d) {
          d3.select(this).style('font-size', d => {
            return Math.min((y(d.y + d.dy) - y(d.y)) * 0.2, 24) + 'px';
          })
          .selectAll('.ptext')
          .call(text)
          .style('fill-opacity', 1);
        });

      t1.selectAll('rect').call(rect);
      t2.selectAll('rect').call(rect);

      // Remove the old node when the transition is finished.
      t1.remove().each('end', function() {
        svg.style('shape-rendering', 'crispEdges');
        transitioning = false;

        if (typeof transitionSubscriber === 'function') {
          transitionSubscriber();
        }

        transitionSubscriber = null;
      });
    }

    return { g: g, transition: transition };
  }

  function text(text) {
    text
      .attr('x', function(d) {
        return x(d.x);
      })
      .attr('y', function(d) {
        return y(d.y) + this.getBoundingClientRect().height/2 + "px";
      })
      .style('fill', '#ffffff')
      .selectAll("tspan.method-text")
      .attr('x', function (d) {
        return x(d.x);
      });

    text.style('opacity', function(d) {

      var fontSize = Number(this.parentNode.style.fontSize.replace("px", ""));

      return fontSize < 10 ||
        this.getComputedTextLength() > x(d.x + d.dx) - x(d.x) ||
        this.getBoundingClientRect().height > y(d.y + d.dy) - y(d.y)
        ? 0
        : 1;
    });
  }

  function rect(rect) {
    rect
      .attr('x', function(d) {
        return x(d.x);
      })
      .attr('y', function(d) {
        return y(d.y);
      })
      .attr('width', function(d) {
        return x(d.x + d.dx) - x(d.x);
      })
      .attr('height', function(d) {
        return y(d.y + d.dy) - y(d.y);
      });
  }

  return {
    transition: globalTransition,
    display,
    reset: () => display(root)
  };
}
const getGroupingFactor = (groupKey, bankNames) => {
  if (groupKey === 'method') {
    return d =>
      d[groupKey] === 'card' || d[groupKey] === 'emi' ? 'card' : d[groupKey];
  } else if (groupKey === 'issuer') {
    return d =>
      d[groupKey]
        ? bankNames[d[groupKey]] || d[groupKey]
        : getGroupingFactor('bank', bankNames)(d);
  } else if (groupKey === 'bank') {
    return d => bankNames[d[groupKey]] || d[groupKey];
  }

  return d => d[groupKey];
};

const makeCSVData = (data, bankNames, groupTitleMap) => {

  const csvHeader = ["#"].concat(paymentMethodsColumns.map(titleCase))
                         .concat(["Total(Paise)", "%Share"]),
        csvBody = [],
        csvFooter = paymentMethodsColumns.map(i => "").concat(["Total"]);

  let total = 0;

  let rows = data.map((record, index) => {
  
    let body = paymentMethodsColumns.map(columnName => {

                 let value = record[columnName];

                 if (columnName === "bank" || columnName === "issuer") {

                   value = bankNames[value];
                 } else if (columnName === "method") {
                 
                   value = groupTitleMap[value] || titleCase(value);
                 } else {
                 
                   value = titleCase(value);
                 }
                  
                 return value;
               });

    total += record.value;

    body.push(record.value);

    return body;
  });

  csvFooter.push(total);

  rows.sort((item1, item2) => item1[0] <= item2[0] ? -1 : 1);

  rows.forEach((row, index) => {
  
    row.unshift(index + 1);
    row.push((row[row.length - 1] / total * 100).toFixed(2) + "%");
  });


  rows.unshift(csvHeader);
  rows.push(csvFooter);

  return arrayToCsvDataUrl(rows);
}

export default function renderTreemap(
  node,
  res,
  d3,
  onTransition,
  onShowTooltip,
  onHideTooltip,
  groupTitleMap,
  bankNames
) {
  node.innerHTML = '';

  const csvUrl = makeCSVData(res, bankNames, groupTitleMap);

  res = d3
    .nest()
    .key(getGroupingFactor('method'))
    .entries(res);

  res.forEach(item => {
    const key = item.key,
      nester = d3.nest();

    let grouper = null;

    if (key === 'card') {
      grouper = nester
        .key(getGroupingFactor('type'))
        .key(getGroupingFactor('issuer', bankNames))
        .key(getGroupingFactor('network'));
    } else if (key === 'netbanking') {
      grouper = nester.key(getGroupingFactor('bank', bankNames));
    } else if (key === 'wallet') {
      grouper = nester.key(getGroupingFactor('wallet'));
    }

    return grouper && (item.values = grouper.entries(item.values));
  });

  const treemapApi =  main(
    node,
    { width: node.clientWidth },
    { key: 'All Methods', values: res },
    d3,
    onTransition,
    onShowTooltip,
    onHideTooltip,
    groupTitleMap || {}
  );

  treemapApi.csv = csvUrl;

  return treemapApi;
}
