import React, { Component } from 'react';

import Definition from 'rzp/ui/Definition';
import Highcharts from 'rzp/ui/Highcharts';

import { tabsMeta } from './data';

const chartOptions = {
  xAxis: {
    type: 'datetime',
  },
  yAxis: {
    title: {
      text: 'Count',
    },
  },
};

class Panel extends Component {
  constructor(props) {
    super(props);

    this.meta = tabsMeta[props.tabName];
    this.handleGroupingChange = this.handleGroupingChange.bind(this);
  }

  handleGroupingChange(e) {
    const { tabName, onGroupingChange } = this.props;

    return onGroupingChange && onGroupingChange(tabName, e.target.value);
  }

  render() {
    const { selectedGrouping, data } = this.props,
      histogramOptions = {
        ...chartOptions,
        series: [
          {
            type: 'area',
            data: data.histogram,
          },
        ],
      },
      { grouping, options } = this.meta;

    if (!data.diff) {
      return <center>Loading...</center>;
    }

    return (
      <div>
        <div className="clearfix panel">
          <div className="pull-left">
            <Definition>
              <h3>{data.diff.value}</h3>
              <span className="text-fade">As compared to:</span>
            </Definition>
          </div>
          <div className="pull-right">
            {grouping.length > 0 && (
              <select
                value={selectedGrouping}
                onChange={this.handleGroupingChange}
              >
                {grouping.map((item, index) => {
                  return (
                    <option value={item.value} key={index}>
                      {item.text}
                    </option>
                  );
                })}
              </select>
            )}
            {options.length > 0 && <button>...</button>}
          </div>
        </div>
        <Highcharts options={histogramOptions} />
      </div>
    );
  }
}

export default Panel;
