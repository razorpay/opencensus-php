/* eslint-disable babel/no-invalid-this */
import sanitizer from 'common/utils/xss-sanitizer';

function customTooltip(tooltipModel) {
  if (!tooltipModel) return;

  // Tooltip Element
  let tooltipEl = document.getElementById('chartjs-tooltip');

  if (!tooltipEl) {
    tooltipEl = document.createElement('div');
    tooltipEl.id = 'chartjs-tooltip';
    tooltipEl.innerHTML = sanitizer(
      '<div className="tooltip-wrapper"></div><div className="tooltip-caret"></div>',
    );
    document.body.appendChild(tooltipEl);
  }

  const {
    opacity = 0,
    body,
    caretX = 0,
    caretY = 0,
    caretPadding = 0,
    _bodyFontFamily = '',
    bodyFontSize = 0,
    _bodyFontStyle = '',
    yPadding = 0,
    xPadding = 0,
    backgroundColor = '',
    borderColor = '',
    borderWidth = 0,
    cornerRadius = 0,
    title = [],
    labelColors = [],
  } = tooltipModel;

  if (!tooltipEl) return;

  if (opacity === 0) {
    tooltipEl.style.opacity = '0';
    return;
  }

  tooltipEl.classList.add('risk-chartjs-tooltip');

  const tooltipCaret = tooltipEl.querySelector('.tooltip-caret');

  tooltipCaret.classList.remove('top', 'bottom', 'center', 'left', 'right');
  tooltipCaret.classList.add('left');

  if (body) {
    let innerHtml = '<div className="title-wrap">';

    title.forEach((title) => {
      innerHtml += `<div className="title">${title}</div>`;
    });

    innerHtml += '</div><div className="tooltip-body-wrap">';

    body.forEach(({ lines }, i) => {
      const { backgroundColor, borderColor } = labelColors[i] || {
        backgroundColor: '',
        borderColor: '',
      };
      const colorBoxStyle = `background: ${backgroundColor}; border-color: ${borderColor};`;
      const colorBox = `<span className="label-box" style="${colorBoxStyle}"></span>`;
      innerHtml += `<div className="tooltip-body-list">
        <div className="tooltip-body">${colorBox}${lines[0]}</div>
      </div>`;
    });

    innerHtml += '</div>';

    const tooltipWrapper = tooltipEl.querySelector('.tooltip-wrapper');
    if (tooltipWrapper) tooltipWrapper.innerHTML = sanitizer(innerHtml, { span: ['style'] }); // Allow span tag with style attribute
  }

  const chartCanvas = this?._chart?.canvas;
  if (!chartCanvas) return;

  const { left: chartLeft, top: chartTop } = chartCanvas.getBoundingClientRect();

  const { height = 0, width = 0 } = tooltipEl.getBoundingClientRect();

  const tooltipLeft = caretX - width / 2;
  const tooltipRight = caretX + width;
  const tooltipBottom = caretY + height;

  const defaultCaretPadding = 7;

  let offsetX = caretX - width / 2;
  let offsetY = caretY + caretPadding + defaultCaretPadding;

  let tooltipCaretOffsetX = width / 2;

  if (offsetX < chartLeft) {
    offsetX = offsetX + width / 2.5;
    tooltipCaretOffsetX = caretX - tooltipLeft - width / 2.5;
  } else if (tooltipRight > (this?._chart?.width || 0)) {
    offsetX = caretX - width / 1.1;
    tooltipCaretOffsetX = width / 1.1;
  }

  if (tooltipBottom > (this?._chart?.height || 0)) {
    offsetY = caretY - height - caretPadding - defaultCaretPadding;
    tooltipCaret.classList.add('bottom');
  } else {
    tooltipCaret.classList.add('top');
  }

  if (tooltipCaret) {
    tooltipCaret.style.left = `${tooltipCaretOffsetX - (tooltipCaret.offsetWidth || 0) / 1.5}px`;
  }

  tooltipEl.style.opacity = '1';
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
  tooltipEl.style.borderWidth = `${borderWidth}px`;
  tooltipEl.style.borderStyle = 'solid';
  tooltipEl.style.borderRadius = `${cornerRadius}px`;
}

export default customTooltip;
