import sanitizer from 'common/utils/xss-sanitizer';
import { SCATTER } from './constants';

/* eslint-disable babel/no-invalid-this */
export function custumTooltip(tooltipModel) {
  // Tooltip Element
  let tooltipEl = document.getElementById('chartjs-tooltip');

  const {
    opacity,
    body,
    caretX,
    caretY,
    caretPadding,
    _bodyFontFamily,
    bodyFontSize,
    _bodyFontStyle,
    yPadding,
    xPadding,
    backgroundColor,
    borderColor,
    borderWidth,
    cornerRadius,
    title,
    labelColors,
    dataPoints,
  } = tooltipModel;

  // Create element on first render
  if (!tooltipEl) {
    tooltipEl = document.createElement('div');
    tooltipEl.id = 'chartjs-tooltip';
    tooltipEl.innerHTML = sanitizer(
      '<div class="tooltip-wrapper"></div><div class="tooltip-caret"></div>',
    );
    document.body.appendChild(tooltipEl);
  }

  // Hide if no tooltip
  if (opacity === 0) {
    tooltipEl.style.opacity = 0;
    return;
  }

  // Add a default class to tooltip element.
  tooltipEl.classList.add('sr-chartjs-tooltip');

  // Get caret element.
  const tooltipCaret = tooltipEl.querySelector('.tooltip-caret');

  // Set caret Position
  tooltipCaret.classList.remove('top', 'bottom', 'center', 'left', 'right');
  tooltipCaret.classList.add('left');

  // Set Text
  if (body) {
    const titleLines = title || [];

    let innerHtml = '<div class="title-wrap">';

    titleLines.forEach((title) => {
      innerHtml += `<div class="title">${title}</div>`;
    });

    innerHtml += '</div><div class="tooltip-body-wrap">';

    body.forEach(({ before, lines }, i) => {
      const { type } = this._chart.data.datasets[dataPoints[i].datasetIndex];
      const { backgroundColor, borderColor } = labelColors[i];

      const time = before[0]?.split(',')?.[0] || '';
      const severity = before[0]?.split(',')?.[1] || '';

      const style = `background: ${backgroundColor}; border-color: ${borderColor};`;

      const colorBox = `<span class="label-box" style="${style}"></span>`;

      innerHtml += `<div class="tooltip-body-list">${
        type === SCATTER
          ? `<div class="tooltip-body-before">
          <span class="time">${time}</span>
          <span class="severity ${severity.toLowerCase()}">${severity.toUpperCase()} SEVERITY</span>
        </div>`
          : ''
      }
      <div class="tooltip-body">${colorBox}${lines[0]}</div>
      ${type === SCATTER && i !== body.length - 1 ? '<span class="line"></span>' : ''}</div>`;
    });

    innerHtml += '</div>';

    const tooltipWrapper = tooltipEl.querySelector('.tooltip-wrapper');
    tooltipWrapper.innerHTML = sanitizer(innerHtml, { span: ['style'] }); // Allow span tag with style attr
  }

  // `this` will be the overall tooltip
  const { left: chartLeft, top: chartTop } = this._chart.canvas.getBoundingClientRect();

  // Tooltip height and width
  const { height, width } = tooltipEl.getBoundingClientRect();

  // Calculating the extreme ends of tooltip from the left of the chart canvas
  const tooltipLeft = caretX - width / 2;
  const tooltipRight = caretX + width;
  const tooltipBottom = caretY + height;

  // Default padding from tooltip caret from the chart point.
  const defaultCaretPadding = 7;

  // Initial position on the tooltip itself
  let offsetX = caretX - width / 2;
  let offsetY = caretY + caretPadding + defaultCaretPadding;

  // Initial position of tooltip caret
  let tooltipCaretOffsetX = width / 2;

  // Correcting overflow of tooltip on extreme right and left the sides
  if (offsetX < chartLeft) {
    offsetX = offsetX + width / 2.5;
    tooltipCaretOffsetX = caretX - tooltipLeft - width / 2.5;
  } else if (tooltipRight > this._chart.width) {
    offsetX = caretX - width / 1.1;
    tooltipCaretOffsetX = width / 1.1;
  }

  // Correcting overflow of tooltip on extreme bottom side
  if (tooltipBottom > this._chart.height) {
    offsetY = caretY - height - caretPadding - defaultCaretPadding;
    tooltipCaret.classList.add('bottom');
  } else {
    tooltipCaret.classList.add('top');
  }

  // Adjusting the position of caret on tooltip
  tooltipCaret.style.left = `${tooltipCaretOffsetX - tooltipCaret.offsetWidth / 1.5}px`;

  // Display, position, and set styles for font
  tooltipEl.style.opacity = 1;
  tooltipEl.style.position = 'absolute';
  tooltipEl.style.left = `${chartLeft + window.pageXOffset + offsetX}px`;
  tooltipEl.style.top = `${chartTop + window.pageYOffset + offsetY}px`;
  tooltipEl.style.fontFamily = _bodyFontFamily;
  tooltipEl.style.fontSize = `${bodyFontSize}px`;
  tooltipEl.style.fontStyle = _bodyFontStyle;
  tooltipEl.style.padding = `${yPadding}px ${xPadding}px`;
  tooltipEl.style.pointerEvents = 'none';
  tooltipEl.style.backgroundColor = backgroundColor;
  tooltipEl.style.borderColor = borderColor;
  tooltipEl.style.borderWidth = borderWidth;
  tooltipEl.style.borderStyle = 'solid';
  tooltipEl.style.borderRadius = `${cornerRadius}px`;
}
