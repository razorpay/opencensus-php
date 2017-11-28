import React, { Component } from 'react';
import { Line } from 'react-chartjs-2';

import Definition from 'rzp/ui/Definition';
import defaultChartOptions from 'rzp/ui/Highcharts/defaults';
import Change from 'rzp/ui/Change';

import Legend, {
  LegendItem,
  LegendLabel,
  LegendTitle,
  LegendContent,
} from 'merchant/containers/Home/Legend';

import { timeScale } from 'rzp/utils/chart/index.js';

import { tabsMeta } from './data';

const chartOptions = timeScale({});

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
      { grouping, options } = this.meta;

    if (data.loading) {
      return <center>Loading...</center>;
    }

    return (
      <div className="panel">
        <div className="clearfix p-all">
          <div className="pull-left">
            <Definition>
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
        <div className="chart-container">
          <Line options={chartOptions} data={data.histogram} />
        </div>
      </div>
    );
  }
}

export default Panel;
