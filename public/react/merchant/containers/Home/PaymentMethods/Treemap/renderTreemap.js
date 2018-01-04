import {
  titleCase,
  getFormattedAmount,
  paiseToRupees,
  arrayToCsvDataUrl,
} from 'rzp/utils/rzp-utils';
import { humanReadableIndianCurrency } from 'rzp/utils/numerals';

var defaults = {
  margin: { top: 0, right: 0, bottom: 0, left: 0 },
  rootname: 'TOP',
  format: ',.2f',
  title: '',
  width: 500,
  height: 300,
};

function makeCSVData(data, aggregate = 0, prefix = '', csvData = []) {
  if (data._children) {
    data._children.forEach(item => {
      if (!item.key) {
        return;
      }

      aggregate += item.value;

      csvData.push([
        prefix + item.key.replace(',', ''),
        item.value,
        item.percent + '%',
      ]);

      makeCSVData(item, aggregate, prefix + '      ', csvData);
    });
  }

  return { aggregate, csvData };
}

function main(node, o, data, d3, onTransition, groupTitleMap) {
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

  var colors = {
    card: 'rgb(75, 84, 113)',
    netbanking: 'rgb(95, 127, 185)',
    bank_transfer: 'rgb(117, 194, 216)',
    upi: 'rgb(172, 172, 231)',
    wallet: 'rgb(235, 120, 120)',
    emi: 'rgb(75, 84, 113)',
  };

  initialize(root);
  accumulate(root);
  layout(root);
  console.log(root);
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

  let transitionSubscriber = null;

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
      .on('click', function(d) {
        if (
          d._children.length === 1 &&
          typeof d._children[0].key === 'undefined'
        ) {
          return;
        }

        if (typeof onTransition === 'function') {
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
      .call(rect)
      .append('title')
      .text(function(d) {
        return (
          (d.displayText || d.parent.displayText) +
          ' ( ₹' +
          formatNumber(d.value) +
          ' )'
        );
      });

    g
      .append('rect')
      .attr('class', 'parent')
      .call(rect);

    var t = g
      .append('text')
      .attr('class', 'ptext')
      .attr('dy', '.75em');

    t
      .append('tspan')
      .attr('class', 'amount')
      .style('font-size', '24px')
      .style('line-height', '29px')
      .text(function(d) {
        return humanReadableIndianCurrency(paiseToRupees(d.value));
      });

    t
      .append('tspan')
      .style('font-size', '14px')
      .style('line-height', '17px')
      .attr('dy', '1.5em')
      .attr('class', 'group-name')
      .text(function(d) {
        return d.displayText;
      });

    t
      .append('tspan')
      .style('font-size', '14px')
      .style('line-height', '17px')
      .attr('dy', '1.5em')
      .attr('class', 'percentage')
      .text(d => {
        return d.percent + '%';
      });

    t.append('title').text(function(d) {
      return '₹' + formatNumber(d.value);
    });

    t.call(text);

    g.selectAll('rect').style('fill', function(d) {
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
          .duration(500)
          .ease('expOut'),
        t2 = g2
          .transition()
          .duration(500)
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
        .selectAll('.ptext')
        .call(text)
        .style('fill-opacity', 1);
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

    globalTransition = transition;

    return { g: g, transition: transition };
  }

  function text(text) {
    text.selectAll('tspan').attr('x', function(d) {
      return x(d.x) + 23.5;
    });
    text
      .attr('x', function(d) {
        return x(d.x);
      })
      .attr('y', function(d) {
        return y(d.y) + 15 + 19.5;
      })
      .style('opacity', function(d) {
        return this.getComputedTextLength() > x(d.x + d.dx) - x(d.x) ||
          this.getBoundingClientRect().height > y(d.y + d.dy) - y(d.y)
          ? 0
          : 1;
      })
      .style('fill', '#ffffff');
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

  const csvData = makeCSVData(root),
    csvBody = csvData.csvData,
    csvHeader = ['', 'Amount', '%Share'],
    csvFooter = ['Total', csvData.aggregate];

  csvBody.unshift(csvHeader);
  csvBody.push(csvFooter);

  return {
    transition: globalTransition,
    display,
    reset: () => display(root),
    csv: arrayToCsvDataUrl(csvBody),
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
        : getGroupingFactor('bank')(d);
  } else if (groupKey === 'bank') {
    return d => bankNames[d[groupKey]] || d[groupKey];
  }

  return d => d[groupKey];
};

export default function renderTreemap(
  node,
  res,
  d3,
  onTransition,
  groupTitleMap,
  bankNames
) {
  node.innerHTML = '';

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

  return main(
    node,
    { width: node.clientWidth },
    { key: 'All Methods', values: res },
    d3,
    onTransition,
    groupTitleMap || {}
  );
}
