import {
  humanReadableIndian,
  humanReadableIndianCurrency,
} from 'rzp/utils/numerals';

// tooltip element
const tooltipDOM = document.createElement('div');

let isTooltipHovered = false,
  isInsertedIntoBody = false,
  shouldShowTooltip = false; //set unset by chartjs

tooltipDOM.id = 'chartjs-tooltip';
tooltipDOM.innerHTML = '<div class="custom-tooltip-inner">' + '</div>';

const caret = document.createElement('div'),
  caretWidth = 20,
  caretHeight = 10;
caret.className = 'caret';

const crossHair = document.createElement('div'),
  crossHairWidth = 2;

crossHair.className = 'cross-hair';

const hideTooltip = () => {
    return (
      !isTooltipHovered &&
      !shouldShowTooltip &&
      (tooltipDOM.style.display = 'none')
    );
  },
  showTooltip = () => (tooltipDOM.style.display = 'block');

tooltipDOM.addEventListener('mouseenter', () => (isTooltipHovered = true));
tooltipDOM.addEventListener('mouseleave', () => {
  isTooltipHovered = false;
  hideTooltip();
});

window.addEventListener('scroll', () => {
  isTooltipHovered = false;
  shouldShowTooltip = false;
  hideTooltip();
});

/* function for custom tooltip */
const customToolTip = function(tooltipModel) {
  // Create element on first render
  if (!isInsertedIntoBody) {
    document.body.appendChild(tooltipDOM);
    isInsertedIntoBody = true;
  }

  // Hide if no tooltip - screw chart.js for not providing
  // callbacks before hiding tooltip
  if (!tooltipModel.opacity) {
    shouldShowTooltip = false;

    /*
   * Puts hideTooltip at the end of callback queue
   * as chartjs tries to hide the tooltip just before
   * `mouseenter` is fired on `tooltipDOM`, which would
   * lead to hiding of tooltip just before hovering on it,
   * and makes the tooltip flicker and not useable
   */
    return window.setTimeout(() => {
      hideTooltip();
    });
  } else {
    shouldShowTooltip = true;
    showTooltip();
  }

  const isCurrency = this._chart.options.isCurrency;

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
    innerHtml += `<div class="title">${(isCurrency
      ? humanReadableIndianCurrency
      : humanReadableIndian)(sumOfAllDataPoints)}</div>`;

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
      const labelValue = `<span class="label-value">${(isCurrency
        ? humanReadableIndianCurrency
        : humanReadableIndian)(Number(bodyItems[1].trim()))}</span>`;

      // appending rows with each line
      rows += `<div class="tooltip-row">${labelIcon}${label}${labelValue}</div>`;
    });

    // adding rows to innerHtml
    innerHtml += `<div class="tooltip-body">${rows}</div>`;

    // inserting innerHtml into inner div of chart js tooltip
    var innerTooltip = tooltipDOM.querySelector('.custom-tooltip-inner');
    innerTooltip.innerHTML = innerHtml;

    innerTooltip.appendChild(caret);
    innerTooltip.appendChild(crossHair);
  }

  /**
   * calculation of position of tooltip
   */
  tooltipDOM.style.opacity = 1;
  tooltipDOM.style.top = tooltipModel.caretY - tooltipDOM.clientHeight + 'px';
  tooltipDOM.style.left = tooltipModel.caretX + 'px';
  caret.style.marginLeft = -(caretWidth / 2) + 'px';

  // correcting overflow of tootip on both the sidesi
  const tooltipWidth = tooltipDOM.clientWidth,
    tooltipLeft = tooltipModel.caretX - tooltipWidth / 2,
    tooltipRight = tooltipModel.caretX + tooltipWidth / 2,
    xAxisHeight = this._chart.scales['x-axis-0'].height,
    {
      left: chartLeft,
      right: chartRight,
      bottom: chartBottom,
    } = this._chart.canvas.getBoundingClientRect(),
    crossHairHeight =
      chartBottom - (tooltipModel.caretY + xAxisHeight) + caretHeight;

  crossHair.style.height = crossHairHeight + 'px';
  crossHair.style.marginLeft = -(crossHairWidth / 2) + 'px';

  if (tooltipLeft < chartLeft) {
    const diff = chartLeft - tooltipLeft;

    tooltipDOM.style.left = tooltipModel.caretX + diff + 'px';
    caret.style.marginLeft = -(caretWidth / 2) - diff + 'px';
    crossHair.style.marginLeft = -(crossHairWidth / 2) - diff + 'px';
  } else if (tooltipRight > chartRight) {
    const diff = tooltipRight - chartRight;

    tooltipDOM.style.left = tooltipModel.caretX - diff + 'px';
    caret.style.marginLeft = -(caretWidth / 2) + diff + 'px';
    crossHair.style.marginLeft = -(crossHairWidth / 2) + diff + 'px';
  }
};

function positioner(elements, eventPosition) {
  if (elements.length === 0) {
    return { x: 0, y: 0 };
  }

  const topEle = elements.sort(
      (ele1, ele2) => ele1._model.y - ele2._model.y
    )[0],
    { left, top } = topEle._chart.canvas.getBoundingClientRect();

  return { x: left + topEle._model.x, y: top + topEle._model.y };
}

export { positioner };

export default customToolTip;
