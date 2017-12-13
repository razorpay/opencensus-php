import React, { Component } from 'react';
import { Line } from 'react-chartjs-2';

import Definition from 'rzp/ui/Definition';
import ChangeRange from 'rzp/ui/ChangeRange';
import { BtnGroup, Btn } from 'rzp/ui/BtnGroup';
import { titleCase } from 'rzp/utils/rzp-utils';
import { timeScale } from 'rzp/utils/chart/index.js';

import { tabsMeta, breakdownVals } from './data';
import Legend from 'merchant/components/Home/Legend';

/* function for custom tooltip */
const customToolTip = function(tooltipModel) {
  // Tooltip Element
  var tooltipDOM = document.getElementById('chartjs-tooltip');

  // Create element on first render
  if (!tooltipDOM) {
    tooltipDOM = document.createElement('div');
    tooltipDOM.id = 'chartjs-tooltip';
    tooltipDOM.innerHTML = '<div class="custom-tooltip-inner"></div>';
    document.body.appendChild(tooltipDOM);
  }

  // Hide if no tooltip
  if (!tooltipModel.opacity) {
    tooltipDOM.style.opacity = 0;
    return;
  }

  // Setting the body and title of tooltip
  if (tooltipModel.body) {
    var innerHtml = '';
    const dataPoints = tooltipModel.dataPoints;
    const sumOfAllDataPoints = dataPoints.reduce(
      (tempSum, { yLabel }) => tempSum + yLabel,
      0
    );

    innerHtml += `<div class="title">₹ ${sumOfAllDataPoints}</div>`;

    var bodyLines = tooltipModel.body.map(({ lines }) => lines[0]);
    let rows = '';
    bodyLines.forEach((body, index) => {
      // var colors = tooltipModel.labelColors[i];
      const labelColors = tooltipModel.labelColors;
      const bodyItems = body.split(':');

      const labelIcon = `<span class="label-icon" style="background-color: ${labelColors[
        index
      ].backgroundColor}"></span>`;
      const label = `<span class="label">${bodyItems[0]}</span>`;
      const labelValue = `<span class="label-value">${bodyItems[1].trim()}</span>`;
      rows += `<div class="tooltip-row">${labelIcon}${label}${labelValue}</div>`;
    });

    innerHtml += `<div class="tooltip-body">${rows}</div>`;

    var innerTooltip = tooltipDOM.querySelector('.custom-tooltip-inner');
    innerTooltip.innerHTML = innerHtml;
  }

  var { offsetTop /*offsetLeft*/ } = this._chart.canvas;

  tooltipDOM.style.opacity = 1;
  tooltipDOM.style.left = `${tooltipModel.caretX +
    this._chart.width / this._data.labels.length}px`;
  tooltipDOM.style.top = `${offsetTop + tooltipModel.caretY}px`;
  tooltipDOM.style.padding = `${tooltipModel.yPadding}px ${tooltipModel.xPadding}px`;
};

const chartOptions = {
  ...timeScale({}),
  layout: {
    padding: {
      top: 50,
    },
  },
  tooltips: {
    enabled: false,
    position: 'nearest',
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
      } = this.props,
      dateFormat = 'DD MMM YYYY',
      { grouping, options } = this.meta,
      { loading } = data;

    return (
      <div className="panel p-all">
        <div className="clearfix">
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
              <button className="btn btn-default">
                <i class="fa fa-ellipsis-h" />
              </button>
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
              <Legend data={data.legendData} />
            </div>
          )}
      </div>
    );
  }
}

export default Panel;
