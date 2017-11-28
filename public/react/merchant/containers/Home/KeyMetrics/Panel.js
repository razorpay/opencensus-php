import React, { Component } from 'react';
import { Line } from 'react-chartjs-2';

import Definition from 'rzp/ui/Definition';
import Change from 'rzp/ui/Change';
import { BtnGroup, Btn } from 'rzp/ui/BtnGroup';

import Legend, {
  LegendItem,
  LegendLabel,
  LegendTitle,
  LegendContent,
} from 'merchant/containers/Home/Legend';
import { titleCase } from 'rzp/utils/rzp-utils';
import { timeScale } from 'rzp/utils/chart/index.js';

import { tabsMeta, breakdownVals } from './data';

const chartOptions = timeScale({});

class Panel extends Component {
  constructor(props) {
    super(props);

    this.meta = tabsMeta[props.tabName];
    this.handleGroupingChange = ::this.handleGroupingChange;
    this.handleBreakdownChange = ::this.handleBreakdownChange;
  }

  handleGroupingChange(e) {
    const { tabName, onGroupingChange } = this.props;

    return onGroupingChange && onGroupingChange(tabName, e.target.value);
  }

  handleBreakdownChange(value) {
    const { tabName, onBreakdownChange } = this.props;

    return onBreakdownChange && onBreakdownChange(tabName, value);
  }

  render() {
    const {
        selectedGrouping,
        data,
        startDate,
        endDate,
        selectedBreakdown,
      } = this.props,
      dateFormat = 'DD MMM YYYY',
      { grouping, options } = this.meta,
      { loading } = data;

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
          <div className="pull-right">...</div>
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
          </div>
          <BtnGroup
            className="pull-right"
            value={selectedBreakdown}
            onChange={this.handleBreakdownChange}
          >
            {breakdownVals.map((item, index) => {
              return (
                <Btn value={item} key={index} className="btn-default">
                  {titleCase(item)}
                </Btn>
              );
            })}
          </BtnGroup>
        </div>
        <div className="chart-container">
          {!data.loading &&
            data.histogram && (
              <Line options={chartOptions} data={data.histogram} />
            )}
        </div>
      </div>
    );
  }
}

export default Panel;
