/* eslint-disable babel/no-invalid-this */
import moment from 'moment';

import {
  getFormattedAmountNew,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
} from 'common/utils/rzp-utils';
import { getUser } from 'merchant/store';
import { trackTooltipDeepdive } from './ga';

// tooltip element
const tooltipDOM = document.createElement('div');

let isTooltipHovered = false;
let isInsertedIntoBody = false;
let shouldShowTooltip = false; //set unset by chartjs

tooltipDOM.id = 'chartjs-tooltip';
// eslint-disable-next-line no-useless-concat
tooltipDOM.innerHTML = '<div className="custom-tooltip-inner">' + '</div>';

const caret = document.createElement('div');
const caretWidth = 20;
const caretHeight = 10;
caret.className = 'caret';

const crossHair = document.createElement('div');
const crossHairWidth = 2;

crossHair.className = 'cross-hair';

const hideTooltip = () => {
  return !isTooltipHovered && !shouldShowTooltip && (tooltipDOM.style.display = 'none');
};
const showTooltip = () => (tooltipDOM.style.display = 'block');

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

const breakdownMap = {
  hourly: 'hour',
  daily: 'day',
  weekly: 'isoWeek',
  monthly: 'month',
};

const getDateFormat = (startDate, breakdown) => {
  let format = 'ddd, Do MMM';

  const isSameYear = startDate.isSame(moment(), 'year');

  if (!isSameYear) {
    format += ' YYYY';
  }

  if (breakdown === 'hourly') {
    format += ' HH:mm';
  }

  return format;
};

/* function for custom tooltip */
// eslint-disable-next-line consistent-return, func-names
const customToolTip = function (tooltipModel) {
  const user = getUser();

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

  // prettier-ignore
  const { isCurrency, externalUrl, breakdown, graphStartDate, graphEndDate, noGrouping } =
    this._chart.options;
  const datasets = this._chart.data.datasets;

  /* Setting the body and title of tooltip only if model has body */
  if (tooltipModel.body) {
    // data member to hold going to be rendered html
    let innerHtml = '';

    // calculating the title: sum of all data
    const dataPoints = tooltipModel.dataPoints;
    const sumOfAllDataPoints = dataPoints.reduce((tempSum, { yLabel }) => tempSum + yLabel, 0);
    const startMs = datasets[0].data[dataPoints[0].index].t;
    // start date can not be less than selected start date
    const startDate = moment(Math.max(startMs, graphStartDate));
    // prettier-ignore
    // end date can not be greater than selected end date
    const endDate = moment(
      Math.min(startDate.clone().endOf(breakdownMap[breakdown]).toDate(), graphEndDate),
    );
    const dateFormat = getDateFormat(startDate, breakdown);
    const url = `${externalUrl}?from=${startDate.unix()}&to=${endDate.unix()}&ref=home`;

    let formattedDate = startDate.format(dateFormat);

    if (breakdown !== 'daily') {
      formattedDate += ` - ${endDate.format(breakdown === 'hourly' ? 'HH:mm' : dateFormat)}`;
    }

    const currentMerchantCurrency = user.merchant.currency;
    // appending title to innerHtml
    innerHtml +=
      `<div className="tooltip-title">` +
      `<div>` +
      `<div className="tooltip-amount">${
        isCurrency
          ? getFormattedAmountNew(
              i18CurrencyConversionFromCommonUnitToMinorUnit(sumOfAllDataPoints),
              true,
              currentMerchantCurrency,
            )
          : getFormattedAmountNew(sumOfAllDataPoints, false, currentMerchantCurrency)
      }</div>` +
      `<div className="sec-text tooltip-date">${formattedDate}</div>` +
      `</div>` +
      `<a href="${url}" className="ex-link deepdive-link"` +
      ` target="_blank">` +
      `<svg xmlns="http://www.w3.org/2000/svg">` +
      `<path d="M1.444 11.556V1.444H5.5V0H1.444C.65 0 0 .65 0 1.444v10.112C0 12.35.65 13 1.444 13h10.112C12.35 13 13 12.35 13 11.556V7.5h-1.444v4.056H1.444zM8.873 1.444h1.671L3.467 8.522l1.01 1.011 7.079-7.077v1.671H13V0H8.873v1.444z"/>` +
      `</svg>` +
      `</a>` +
      `</div>`;

    // variable to hold rows
    let rows = '';

    if (!noGrouping) {
      tooltipDOM.className = '';

      const dataPoints = tooltipModel.dataPoints.sort((item1, item2) => {
        return item1.datasetIndex - item2.datasetIndex;
      });

      datasets.forEach((dataset, index) => {
        const { backgroundColor, label } = dataset;
        const dataPoint = dataPoints[index];
        const value = dataPoint.yLabel;

        const labelIcon = `<span className="label-icon" style="background-color: ${backgroundColor}"></span>`;

        const labelHTML = `<span className="label sec-text">${label}</span>`;

        const labelValue =
          `<span className="label-value">` +
          `${
            isCurrency
              ? getFormattedAmountNew(
                  i18CurrencyConversionFromCommonUnitToMinorUnit(value),
                  true,
                  currentMerchantCurrency,
                )
              : getFormattedAmountNew(value, false, currentMerchantCurrency)
          }` +
          `</span>`;

        // appending rows with each line
        rows += `<div className="tooltip-row clearfix">${labelIcon}${labelHTML}${labelValue}</div>`;
      });

      // adding rows to innerHtml
      innerHtml += `<div className="tooltip-body">${rows}</div>`;
    } else {
      tooltipDOM.className = 'no-grouping';
    }
    // inserting innerHtml into inner div of chart js tooltip
    const innerTooltip = tooltipDOM.querySelector('.custom-tooltip-inner');
    innerTooltip.innerHTML = innerHtml; // nosemgrep : https://semgrep.dev/s/swati31196:rzp-insecure-document-method

    innerTooltip.querySelector('.deepdive-link').onclick = trackTooltipDeepdive;

    innerTooltip.appendChild(caret);
    innerTooltip.appendChild(crossHair);
  }

  /**
   * calculation of position of tooltip
   */
  tooltipDOM.style.opacity = 1;
  tooltipDOM.style.top = `${tooltipModel.caretY - tooltipDOM.clientHeight}px`;
  tooltipDOM.style.left = `${tooltipModel.caretX}px`;
  caret.style.marginLeft = `${-(caretWidth / 2)}px`;

  // correcting overflow of tootip on both the sidesi
  const tooltipWidth = tooltipDOM.clientWidth;
  const tooltipLeft = tooltipModel.caretX - tooltipWidth / 2;
  const tooltipRight = tooltipModel.caretX + tooltipWidth / 2;
  const xAxisHeight = this._chart.scales['x-axis-0'].height;
  const {
    left: chartLeft,
    right: chartRight,
    bottom: chartBottom,
  } = this._chart.canvas.getBoundingClientRect();
  const crossHairHeight = chartBottom - (tooltipModel.caretY + xAxisHeight) + caretHeight;

  crossHair.style.height = `${crossHairHeight}px`;
  crossHair.style.marginLeft = `${-(crossHairWidth / 2)}px`;

  if (tooltipLeft < chartLeft) {
    const diff = chartLeft - tooltipLeft;

    tooltipDOM.style.left = `${tooltipModel.caretX + diff}px`;
    caret.style.marginLeft = `${-(caretWidth / 2) - diff}px`;
    crossHair.style.marginLeft = `${-(crossHairWidth / 2) - diff}px`;
  } else if (tooltipRight > chartRight) {
    const diff = tooltipRight - chartRight;

    tooltipDOM.style.left = `${tooltipModel.caretX - diff}px`;
    caret.style.marginLeft = `${-(caretWidth / 2) + diff}px`;
    crossHair.style.marginLeft = `${-(crossHairWidth / 2) + diff}px`;
  }

  // if there is only one point hide crosshair
  crossHair.style.display = this._data.labels.length === 1 ? 'none' : 'block';
};

function positioner(elements) {
  if (elements.length === 0) {
    return { x: 0, y: 0 };
  }

  const topEle = elements.sort((ele1, ele2) => ele1._model.y - ele2._model.y)[0];
  const { left, top } = topEle._chart.canvas.getBoundingClientRect();

  return { x: left + topEle._model.x, y: top + topEle._model.y - 10 };
}

export { positioner };

export default customToolTip;
