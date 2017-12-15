import React, { Component } from 'react';
import { Line } from 'react-chartjs-2';

import Definition from 'rzp/ui/Definition';
import ChangeRange from 'rzp/ui/ChangeRange';
import { BtnGroup, Btn } from 'rzp/ui/BtnGroup';
import { titleCase } from 'rzp/utils/rzp-utils';
import { timeScale } from 'rzp/utils/chart/index.js';
import { humanReadableIndian } from 'rzp/utils/numerals';

import { tabsMeta, breakdownVals } from './data';
import Legend from 'merchant/components/Home/Legend';

/* function for custom tooltip */
const customToolTip = function(tooltipModel) {
  console.log(tooltipModel.caretY);
  console.log(this._chart.canvas.getBoundingClientRect());
  console.log(this);
  // Tooltip Element
  var tooltipDOM = document.getElementById('chartjs-tooltip');

  // Create element on first render
  if (!tooltipDOM) {
    tooltipDOM = document.createElement('div');
    tooltipDOM.id = 'chartjs-tooltip';
    tooltipDOM.innerHTML =
      '<div class="custom-tooltip-inner"></div><div class="caret"/>';
    document.body.appendChild(tooltipDOM);
  }

  // Hide if no tooltip
  // TODO: wants a better option than opacity
  if (!tooltipModel.opacity) {
    tooltipDOM.style.opacity = 0;
    return;
  }

  /* Setting the body and title of tooltip only if model has body */
  if (tooltipModel.body) {
    // data member to hold going to be rendered html
    var innerHtml = '';

    // calculating the title: sum of all data
    const dataPoints = tooltipModel.dataPoints;
    const sumOfAllDataPoints = dataPoints.reduce(
      (tempSum, { yLabel }) => tempSum + yLabel,
      0
    );

    // appending title to innerHtml
    innerHtml += `<div class="title">₹ ${humanReadableIndian(
      sumOfAllDataPoints
    )}</div>`;

    /**
     * extracting lines inside each elements of body array has actual text to be rendered
     */
    var bodyLines = tooltipModel.body.map(({ lines }) => lines[0]);

    // variable to hold rows
    let rows = '';
    bodyLines.forEach((body, index) => {
      const labelColors = tooltipModel.labelColors;

      /**
       * body is a string with format label: value
       * splitting it into array will give us [label, value];
       */
      const bodyItems = body.split(':');

      // label icon is the colored (color same as in legend) square box
      const labelIcon = `<span class="label-icon" style="background-color: ${labelColors[
        index
      ].backgroundColor}"></span>`;

      // first element of bodyItems is label
      const label = `<span class="label">${bodyItems[0]}</span>`;

      // second element of bodyItem is value corresponding to label extracted in first line
      const labelValue = `<span class="label-value">${humanReadableIndian(
        Number(bodyItems[1].trim())
      )}</span>`;

      // appending rows with each line
      rows += `<div class="tooltip-row">${labelIcon}${label}${labelValue}</div>`;
    });

    // adding rows to innerHtml
    innerHtml += `<div class="tooltip-body">${rows}</div>`;

    // inserting innerHtml into inner div of chart js tooltip
    var innerTooltip = tooltipDOM.querySelector('.custom-tooltip-inner');
    innerTooltip.innerHTML = innerHtml;
  }

  /**
   * calculation of position of tooltip
   */

  var { offsetTop /*offsetLeft*/ } = this._chart.canvas;

  /* refer styling file */
  // and width property of #chartjs-tooltip
  const widthOfTooltip = 218;

  // border size of #chart-js-tooltip
  const heightOfCaret = 10;
  /* * */

  /* calculations for left of tooltip */

  // width of y-axis of chart
  const widthOfYAxis = this._chart.boxes.find(({ id }) => id === 'y-axis-0')
    .width;

  // chartX: absolute position of chart from left edge
  // fullChartRight: absolute right edge of full chart
  const {
    x: chartX,
    y: chartY,
    right: fullChartRight,
  } = this._chart.canvas.getBoundingClientRect();

  // distance of right edge of inner chart (where actual graph is rendered) area from edge of body
  const chartRight = this._chart.boxes[0].chart.chartArea.right;

  // no of data points on x-axis
  const noOfDataLabels = this._data.labels.length;

  // index of current point (horizontally) hovered on x-axis
  const dataPointIndex = tooltipModel.dataPoints
    ? tooltipModel.dataPoints[0].index
    : 0;

  // width of span between to data points on x-axis
  const widthOfEachLabel = Math.round(
    (chartRight - widthOfYAxis) / (noOfDataLabels - 1)
  );

  // opactity is 1 to display tooltip
  tooltipDOM.style.opacity = 1;

  // max left tooltip can have - should not exceed extreme right of full chart
  const tooltipMaxLeft = fullChartRight - widthOfTooltip;

  // left of tooltip is absoluteLeft of chart + widthOfYAxis + widthOfEachLabel (added according to index)
  // left computed for tooltip
  const computedLeft =
    chartX / 2 + widthOfYAxis + widthOfEachLabel * dataPointIndex;

  /* * */

  /* calculating different elements for top of tooltip */

  // extract the actual height of tooltip DOM
  const actualTooltipHeight = Number(
    window.getComputedStyle(tooltipDOM).height.replace('px', '')
  );

  // top of chart w.r.t document top
  const chartTop = chartY + window.scrollY;
  /* * */

  /* calculation of elements for caret position */

  // small inverted arrow attached to tooltip
  const caret = tooltipDOM.querySelector('.caret');

  /* * */

  /* applying the formulae for top, left and caret position */

  // left of tooltip cannot exceed tooltipMaxLeft
  tooltipDOM.style.left = `${Math.min(tooltipMaxLeft, computedLeft)}px`;

  // top of tooltip is chart top (w.r.t doc.) - height + the caretY - caret height
  tooltipDOM.style.top = `${chartTop -
    actualTooltipHeight +
    tooltipModel.caretY -
    heightOfCaret}px`;

  // resetting any left given to caret in previous iteration
  caret.style.left = '';

  // shifting caret towards right if tooltip has max left
  if (computedLeft > tooltipMaxLeft) {
    caret.style.left = `${0.48 * 218 + (computedLeft - tooltipMaxLeft)}px`;
  }
  /* * */

  // setting the padding
  // BUG: not getting reflected in browser
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
              <Legend
                data={data.legendData}
                valueTransformer={humanReadableIndian}
              />
            </div>
          )}
      </div>
    );
  }
}

export default Panel;
