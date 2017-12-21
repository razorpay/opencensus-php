import { humanReadableIndian } from 'rzp/utils/numerals';

// tooltip element
const tooltipDOM = document.createElement('div');

let isTooltipHovered = false,
  isInsertedIntoBody = false,
  shouldShowTooltip = false;

tooltipDOM.id = 'chartjs-tooltip';
tooltipDOM.innerHTML = '<div class="custom-tooltip-inner">' + '</div>';

const caretHtml = '<div class="caret"/>';

const hideTooltip = () => {
    return window.setTimeout(() => {
      return (
        !isTooltipHovered &&
        !shouldShowTooltip &&
        (tooltipDOM.style.display = 'none')
      );
    });
  },
  showTooltip = () => (tooltipDOM.style.display = 'block');

tooltipDOM.addEventListener('mouseenter', () => (isTooltipHovered = true));
tooltipDOM.addEventListener('mouseleave', () => {
  isTooltipHovered = false;
  hideTooltip();
});

/* function for custom tooltip */
const customToolTip = function(tooltipModel) {
  // Create element on first render
  if (!isInsertedIntoBody) {
    document.body.appendChild(tooltipDOM);
    isInsertedIntoBody = true;
  }

  // Hide if no tooltip
  // TODO: wants a better option than opacity
  if (!tooltipModel.opacity) {
    shouldShowTooltip = false;
    return hideTooltip();
  } else {
    shouldShowTooltip = true;
    showTooltip();
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
    innerHtml += `<div class="tooltip-body">${rows}</div>${caretHtml}`;

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
    tooltipModel.caretY}px`;

  // resetting any left given to caret in previous iteration
  caret.style.left = '';

  // shifting caret towards right if tooltip has max left
  if (computedLeft > tooltipMaxLeft) {
    caret.style.left = `${0.48 * 218 + (computedLeft - tooltipMaxLeft)}px`;
  }
};

export default customToolTip;
