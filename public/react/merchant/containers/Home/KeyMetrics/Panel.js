import React, { Component } from 'react';
import { Line } from 'react-chartjs-2';

import Definition from 'rzp/ui/Definition';
import ChangeRange from 'rzp/ui/ChangeRange';
import { BtnGroup, Btn } from 'rzp/ui/BtnGroup';
import { titleCase } from 'rzp/utils/rzp-utils';
import { timeScale } from 'rzp/utils/chart/index.js';
import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';

import { tabsMeta, breakdownVals } from './data';
import Legend from 'merchant/components/Home/Legend';
import LastUpdated from 'merchant/components/Home/LastUpdated';
import MoreOptionsButton from 'merchant/components/Home/MoreOptionsButton';
import customToolTip from 'merchant/containers/Home/KeyMetrics/customTooltip';

const chartOptions = {
  ...timeScale({}),
  layout: {
    padding: {
      left: 14,
      right: 14,
    },
  },
  tooltips: {
    enabled: false,
    position: 'nearest',
    caretPadding: 0,
    yPadding: 0,
    xPadding: 0,
    /* custom tooltip */
    custom: customToolTip,
  },
};

/*
 * This component is responsible to show tab content in `KeyMetrics`
 * component.
 */

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
        lastUpdatedAt,
        isCurrency,
      } = this.props,
      dateFormat = 'DD MMM YYYY',
      { grouping, options } = this.meta,
      { loading } = data;

    chartOptions.isCurrency = isCurrency;

    return (
      <div className="panel key-metrics-container">
        <div className="p-all">
          <div className="clearfix panel-topbar">
            <div className="pull-left">
              <ChangeRange previous={20} current={17} />
              <Definition>
                <span className="text-fade">
                  Compared to
                  <strong> {startDate.format(dateFormat)} </strong>
                  to
                  <strong> {endDate.format(dateFormat)} </strong>
                </span>
              </Definition>
            </div>
            <div className="panel-actions pull-right">
              <div className="panel-action-item">
                {grouping.length > 0 && (
                  <select
                    className="form-control"
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
                className="panel-action-item"
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
              <div className="panel-action-item">
                <MoreOptionsButton />
              </div>
            </div>
          </div>
          <div className="chart-container">
            {!data.loading &&
              data.histogram && (
                <Line options={chartOptions} data={data.histogram} />
              )}
          </div>
          {!data.loading &&
            data.legendData && (
              <div className="p-t">
                <Legend
                  data={data.legendData}
                  valueTransformer={
                    isCurrency
                      ? humanReadableIndianCurrency
                      : humanReadableIndian
                  }
                />
              </div>
            )}
        </div>
        {!data.loading && (
          <div className="panel-footer">
            <LastUpdated at={lastUpdatedAt} />
          </div>
        )}
      </div>
    );
  }
}

export default Panel;
