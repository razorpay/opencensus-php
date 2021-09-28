import { titleCase, paiseToRupees, arrayToCsvDataUrl } from 'common/utils/rzp-utils';
import { humanReadableIndian, humanReadableIndianCurrency } from 'common/utils/numerals';

import { getPaymentMethodColor } from 'merchant/components/Home/data';
import { paymentMethodsColumns } from 'merchant/containers/Home/PaymentMethods/data';

import { trackTreemapClick } from '../ga';

const defaults = {
  margin: { top: 0, right: 0, bottom: 0, left: 0 },
  rootname: 'TOP',
  format: ',.2f',
  title: '',
  width: 500,
  height: 300 - 24, //leaving 24px at the bottom
};

// eslint-disable-next-line max-params
function main(
  node,
  o,
  data,
  isCurrency,
  d3,
  onTransition,
  onShowTooltip,
  onHideTooltip,
  groupTitleMap,
) {
  let root, transitioning, g1;
  const opts = { ...defaults, ...o };
  const formatNumber = isCurrency
    ? (value) => humanReadableIndianCurrency(paiseToRupees(value))
    : humanReadableIndian;
  const rname = opts.rootname;
  const margin = opts.margin;

  node.style.width = `${opts.width}px`;
  node.style.height = `${opts.height}px`;
  node.style.position = 'realtive';

  const width = opts.width - margin.left - margin.right;
  const height = opts.height - margin.top - margin.bottom;

  const x = d3.scale.linear().domain([0, width]).range([0, width]);

  const y = d3.scale.linear().domain([0, height]).range([0, height]);

  const treemap = d3.layout
    .treemap()
    .children(function fn(d, depth) {
      return depth ? null : d._children;
    })
    .sort(function fn(a, b) {
      return a.value - b.value;
    })
    .size([1, 1])
    .round(false);

  const svg = d3
    .select(node)
    .append('svg')
    .attr('width', width + margin.left + margin.right)
    .attr('height', height + margin.bottom + margin.top)
    .style('margin-left', `${-margin.left}px`)
    .style('margin.right', `${-margin.right}px`)
    .append('g')
    .attr('transform', `translate(${margin.left},${margin.top})`)
    .style('shape-rendering', 'crispEdges');

  if (data instanceof Array) {
    root = { key: rname, values: data };
  } else {
    root = data;
  }

  const colors = {};
  const aliases = { emi: 'card' };

  initialize(root);
  accumulate(root);

  /*
   * Populating chart colors based on the values
   * Bigger the values get first colors in the
   * color palette
   */
  [...root.values]
    .sort((item1, item2) => {
      return item2.value - item1.value;
    })
    .forEach(({ key }) => {
      colors[key] = getPaymentMethodColor(titleCase(key));
    });

  Object.keys(aliases).forEach((key) => {
    colors[key] = colors[aliases[key]];
  });

  layout(root);

  let transitionSubscriber = null;
  const maxFontSize = 24;

  const globalTransition = display(root).transition;

  if (typeof onTransition === 'function') {
    onTransition(root, true);
  }

  function initialize(rootCurrent) {
    rootCurrent.x = 0;
    rootCurrent.y = 0;
    rootCurrent.dx = width;
    rootCurrent.dy = height;
    rootCurrent.depth = 0;
  }

  function rollup(nodeCurrent, color) {
    nodeCurrent.color = color || colors[nodeCurrent.method] || 'black';

    if (nodeCurrent.depth === 1 && nodeCurrent.parent) {
      if (!nodeCurrent.percent) {
        const sum = nodeCurrent.parent._children.reduce((result, child) => result + child.value, 0);

        nodeCurrent.percent = ((nodeCurrent.value / sum) * 100).toFixed(2);
      }

      rollup(nodeCurrent.parent, nodeCurrent.color);
    }
  }

  // Aggregate the values for internal nodes. This is normally done by the
  // treemap layout, but not here because of our custom implementation.
  // We also take a snapshot of the original children (_children) to avoid
  // the children being overwritten when when layout is computed.
  function accumulate(d) {
    d.displayText = '';

    if (d.key) {
      d.displayText =
        d.key.indexOf('__bank') >= 0
          ? d.key.replace('__bank', '')
          : groupTitleMap[d.key] || titleCase(d.key);
    }

    // eslint-disable-next-line no-cond-assign
    return (d._children = d.values)
      ? (d.value = d.values.reduce(function fn(p, v) {
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
      d._children.forEach(function fn(c) {
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
    return d._children?.length > 0 && typeof d._children[0].key !== 'undefined';
  }

  function display(d) {
    g1 = svg.append('g').datum(d).attr('class', 'depth');

    const g = g1.selectAll('g').data(d._children).enter().append('g');

    g.filter(function fn(datum) {
      return datum.key && datum._children;
    })
      .classed('children', true)
      .style('cursor', function fn(datum) {
        return canBeZoomed(datum) ? 'pointer' : 'default';
      })
      .style('font-size', (datum) => {
        return `${Math.min((y(datum.y + datum.dy) - y(datum.y)) * 0.2, maxFontSize)}px`;
      })
      .on('mouseenter', function fn(datum) {
        const hasZoom = canBeZoomed(datum);

        onShowTooltip({
          amount: datum.value,
          percent: datum.percent,
          label: datum.displayText,
          canBeZoomed: hasZoom,
        });

        if (hasZoom) {
          // eslint-disable-next-line babel/no-invalid-this
          d3.select(this).selectAll('rect.parent').style('fill-opacity', 0.1);
        }
      })
      .on('mouseleave', function fn(datum) {
        if (canBeZoomed(datum)) {
          // eslint-disable-next-line babel/no-invalid-this
          d3.select(this).selectAll('rect.parent').style('fill-opacity', 0);
        }
      })
      .on('click', function fn(datum) {
        trackTreemapClick(datum);

        if (canBeZoomed(datum) && typeof onTransition === 'function') {
          onTransition(datum);
        }
      });

    const children = g
      .selectAll('.child')
      .data(function fn(datum) {
        return datum._children || [datum];
      })
      .enter()
      .append('g');

    children.append('rect').attr('class', 'child').call(rect);

    g.append('rect').attr('class', 'parent').call(rect);

    const t = g
      .append('text')
      .attr('dx', '1em')
      .attr('dy', '0.75em')
      .attr('class', 'ptext')
      .style('font-size', '1em');

    t.append('tspan')
      .attr('class', 'amount method-text')
      .style('font-size', '1em')
      .attr('dx', '1em')
      .text(function fn(datum) {
        return formatNumber(datum.value);
      })
      .append('tspan')
      .attr('class', 'amount-percent')
      .attr('dx', 6)
      .style('font-size', '0.6em')
      .style('fill', '#ffffff')
      .style('fill-opacity', 0.6)
      .text(function fn(datum) {
        return `(${datum.percent}%)`;
      });

    t.append('tspan')
      .style('font-size', '0.6em')
      .attr('dx', '1.67em') // inverse of 0.6
      .attr('dy', '1.5em')
      .attr('class', 'group-name method-text')
      .text(function fn(datum) {
        return datum.displayText;
      });

    t.call(text);

    g.selectAll('rect.child').style('fill', function fn(datum) {
      return datum.color;
    });

    function transition(datum) {
      if (transitioning || !datum) {
        transitionSubscriber = () => transition(datum);
        return;
      }

      transitioning = true;

      const oldG1 = g1;

      const g2 = display(datum).g;
      const t1 = oldG1.transition().duration(100).ease('expOut');
      const t2 = g2.transition().duration(100).ease('expOut');

      // Update the domain only after entering new elements.
      x.domain([datum.x, datum.x + datum.dx]);
      y.domain([datum.y, datum.y + datum.dy]);

      // Enable anti-aliasing during the transition.
      svg.style('shape-rendering', null);

      // Draw child nodes on top of parent nodes.
      svg.selectAll('.depth').sort(function fn(a, b) {
        return a.depth - b.depth;
      });

      // Fade-in entering text.
      g2.selectAll('text').style('fill-opacity', 0);

      // Transition to the new view.
      t1.selectAll('.ptext').call(text).style('fill-opacity', 0);

      t2.each('end', function fn() {
        // eslint-disable-next-line babel/no-invalid-this
        d3.select(this)
          .style('font-size', (datumCurrent) => {
            return `${Math.min(
              (y(datumCurrent.y + datumCurrent.dy) - y(datumCurrent.y)) * 0.2,
              24,
            )}px`;
          })
          .selectAll('.ptext')
          .call(text)
          .style('fill-opacity', 1);
      });

      t1.selectAll('rect').call(rect);
      t2.selectAll('rect').call(rect);

      // Remove the old node when the transition is finished.
      t1.remove().each('end', function fn() {
        svg.style('shape-rendering', 'crispEdges');
        transitioning = false;

        if (typeof transitionSubscriber === 'function') {
          transitionSubscriber();
        }

        transitionSubscriber = null;
      });
    }

    return { g, transition };
  }

  function text(textCurrent) {
    textCurrent
      .attr('x', function fn(d) {
        return x(d.x);
      })
      .attr('y', function fn(d) {
        // eslint-disable-next-line babel/no-invalid-this
        return `${y(d.y) + this.getBoundingClientRect().height / 2}px`;
      })
      .style('fill', '#ffffff')
      .selectAll('tspan.method-text')
      .attr('x', function fn(d) {
        return x(d.x);
      });

    textCurrent.style('opacity', function fn(d) {
      // eslint-disable-next-line babel/no-invalid-this
      const fontSize = Number(this.parentNode.style.fontSize.replace('px', ''));

      return fontSize < 10 ||
        // eslint-disable-next-line babel/no-invalid-this
        this.getComputedTextLength() > x(d.x + d.dx) - x(d.x) ||
        // eslint-disable-next-line babel/no-invalid-this
        this.getBoundingClientRect().height > y(d.y + d.dy) - y(d.y)
        ? 0
        : 1;
    });
  }

  function rect(rectCurrent) {
    rectCurrent
      .attr('x', function fn(d) {
        return x(d.x);
      })
      .attr('y', function fn(d) {
        return y(d.y);
      })
      .attr('width', function fn(d) {
        return x(d.x + d.dx) - x(d.x);
      })
      .attr('height', function fn(d) {
        return y(d.y + d.dy) - y(d.y);
      });
  }

  return {
    transition: globalTransition,
    display,
    reset: () => display(root),
  };
}

const getBankName = (name, bankNames) => {
  return `${bankNames[name] || name || 'Unknown'}__bank`;
};

const getGroupingFactor = (groupKey, bankNames) => {
  if (groupKey === 'method') {
    return (d) => (d[groupKey] === 'card' || d[groupKey] === 'emi' ? 'card' : d[groupKey]);
  } else if (groupKey === 'issuer') {
    return (d) =>
      d[groupKey] ? getBankName(d[groupKey], bankNames) : getGroupingFactor('bank', bankNames)(d);
  } else if (groupKey === 'bank') {
    return (d) => getBankName(d[groupKey], bankNames);
  }

  return (d) => d[groupKey];
};

const makeCSVData = (data, bankNames, groupTitleMap) => {
  const csvHeader = [].concat(paymentMethodsColumns.map(titleCase)).concat(['Amount', '%Share']);

  let total = 0;

  const rows =
    data instanceof Array
      ? data.map((record) => {
          const body = paymentMethodsColumns.map((columnName) => {
            let value = record[columnName];

            if (columnName === 'bank' || columnName === 'issuer') {
              value = bankNames[value] || 'Unknown';
            } else if (columnName === 'method') {
              value = groupTitleMap[value] || titleCase(value);
            } else {
              value = titleCase(value);
            }

            return value;
          });

          total += record.value;

          body.push(record.value);

          return body;
        })
      : [];

  rows.sort((item1, item2) => (item1[0] <= item2[0] ? -1 : 1));

  rows.forEach((row) => {
    if (typeof row !== 'undefined' && row?.length > 0) {
      row.push(`${((row[row.length - 1] / total) * 100).toFixed(2)}%`);
    }
  });

  rows.unshift(csvHeader);

  return arrayToCsvDataUrl(rows);
};

// eslint-disable-next-line max-params
export default function renderTreemap(
  node,
  res,
  isCurrency,
  d3,
  onTransition,
  onShowTooltip,
  onHideTooltip,
  groupTitleMap,
  bankNames,
) {
  if (!d3 || !bankNames || !node || !res) {
    return {};
  }

  node.innerHTML = '';

  const csvUrl = makeCSVData(res, bankNames, groupTitleMap);

  res = d3.nest().key(getGroupingFactor('method')).entries(res);

  res.forEach((item) => {
    const key = item.key;
    const nester = d3.nest();

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

  const treemapApi = main(
    node,
    { width: node.clientWidth },
    { key: 'All Methods', values: res },
    isCurrency,
    d3,
    onTransition,
    onShowTooltip,
    onHideTooltip,
    groupTitleMap || {},
  );

  treemapApi.csv = csvUrl;

  return treemapApi;
}
