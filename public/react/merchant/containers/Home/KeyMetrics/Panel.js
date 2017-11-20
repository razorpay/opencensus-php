import React, { Component } from 'react';

import Definition from 'rzp/ui/Definition';
import Highcharts from 'rzp/ui/Highcharts';
import Change from 'rzp/ui/Change';

import { tabsMeta } from './data';

const chartOptions = {
  chart: {
    height: 350,
    spacingTop: 10,
  },
  xAxis: {
    type: 'datetime',
  },
  plotOptions: {
    area: {
      stacking: true,
      animation: {
        duration: 0,
      },
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
    const { selectedGrouping, data, startDate, endDate } = this.props,
      dateFormat = 'DD MMM YYYY',
      histogramOptions = {
        ...chartOptions,
        series:
          data.histogram &&
          data.histogram.map(item => {
            return {
              type: 'area',
              data: item.data,
              name: item.name,
            };
          }),
      },
      { grouping, options } = this.meta;

    if (!data.diff) {
      return <center>Loading...</center>;
    }

    return (
      <div className="panel">
        <div className="clearfix p-all">
          <div className="pull-left">
            <Definition>
              <h4>
                <Change value={data.diff.value} />
              </h4>
              <span className="text-fade">
                As compared to: {startDate.format(dateFormat)} to{' '}
                {endDate.format(dateFormat)}
              </span>
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
