import React, { Component } from 'react';
import Chartist from 'chartist';

export class Single extends Component {
  render() {
    let { title, value } = this.props;

    return (
      <div>
        <header>{title}</header>
        {value}
      </div>
    );
  }
}

export class Chart extends Component {
  render() {
    let { title, data, options, type, legends } = this.props;
    return (
      <div>
        <header>
          {title}
          <Legends legends={legends} />
        </header>

        <div ref={el => el && data && makeChart(el, data, options, type)} />
      </div>
    );
  }
}

class Legends extends Component {
  render() {
    let { legends } = this.props;
    if (!legends || legends.length === 0) return null;

    const legendsToShow = legends.map((legend, i) => {
      console.log('legendMapI', i);
      return (
        <div
          className={`ct-legend ct-series-${String.fromCharCode(97 + i)}`}
          key={`legend_${i}`}
        >
          <div className="ct-background" />
          {legend.toUpperCase()}
        </div>
      );
    });

    return <div className="ct-legend-container">{legendsToShow}</div>;
  }
}

function makeChart(el, data, options, type) {
  switch (type) {
    case 'bar':
      return new Chartist.Bar(el, data, options);
    case 'line':
      return new Chartist.Line(el, data, options);
    default:
      return null;
  }
}

export function priceLabelInterpolationFnc(extra = {}) {
  return function(value, index, labels) {
    let label = labels[index];

    if (label > 100000000) {
      label = Math.round(label / 10000000).toString() + ' cr';
    } else if (label > 1000000) {
      label = Math.round(label / 100000).toString() + ' L';
    } else if (label > 10000) {
      label = Math.round(label / 1000).toString() + ' K';
    }

    if (extra.column === 'base_amount') {
      label = '₹' + label;
    } else if (extra.agg_type === 'success_rate') {
      label = '%' + label;
    }
    return label;
  };
}

export function dateLabelInterpolationFnc(extra = {}) {
  const { maxLabels } = extra;
  return function(value, index, labels) {
    const labelInterpolationMod = maxLabels
      ? Math.ceil(labels.length / maxLabels)
      : 1;
    if (index % labelInterpolationMod === 0)
      return new Date(value * 1000).toDateString();
    return null;
  };
}

export function getPriceChartOptions(type, extra) {
  switch (type) {
    case 'bar':
      return {
        axisX: {
          labelInterpolationFnc: function(value, index, label) {
            return label[index].charAt(0).toUpperCase() + label[index].slice(1);
          },
        },
        axisY: {
          labelInterpolationFnc: priceLabelInterpolationFnc(extra),
        },
      };
    case 'line':
      return {
        showPoint: false,
        lineSmooth: false,
        axisX: {
          labelInterpolationFnc: dateLabelInterpolationFnc(extra),
        },
        axisY: {
          labelInterpolationFnc: priceLabelInterpolationFnc(extra),
        },
      };
    default:
      return {};
  }
}
