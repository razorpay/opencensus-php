import { i18HumanReadableNumerals } from 'common/utils/numerals';
let MAX_VALUE = 0;
let CENTER = 0;

export const buildFunnelChartData = (data) => {
  if (data) {
    const keys = Object.keys(data).filter((k) => !['title', 'unit', 'updated_at'].includes(k));
    // const keys = Object.values(FUNNEL_STEPS);
    const values = keys.map((key) => data?.[key]?.value);
    const titles = keys.map((key) => data?.[key]?.title);
    MAX_VALUE = values[0];
    const labels = values.map((value, index) => {
      const percent = ((value / MAX_VALUE) * 100).toFixed(2);
      return `${titles[index]} (${percent}%)`;
    });

    const dataset = [[0, 100]];
    values.slice(1).forEach((val) => {
      const diff = (100 - (val / MAX_VALUE) * 100) / 2;
      const chartDiff = [diff, 100 - diff];
      dataset.push(chartDiff);
    });
    return {
      labels,
      datasets: [
        {
          indexAxis: 'y',
          label: 'Magic Checkout Funnel',
          data: dataset,
          backgroundColor: ['#7196fb', '#5b7fe1', '#4467c6', '#3355b2', '#1b3c96'],
          borderWidth: 0,
          borderSkipped: false,
          categoryPercentage: 1,
          barPercentage: 1,
        },
      ],
    };
  }
  return null;
};

export const funnelCustomTooltip = (tooltipModel, ctx) => {
  // Tooltip Element
  let tooltipEl = document.getElementById('chartjs-tooltip');

  // Create element on first render
  if (!tooltipEl) {
    tooltipEl = document.createElement('div');
    tooltipEl.id = 'chartjs-tooltip';
    document.body.appendChild(tooltipEl);
  }

  // Hide if no tooltip
  if (tooltipModel.opacity === 0) {
    tooltipEl.style.opacity = '0';
    return;
  }
  tooltipEl.style.zIndex = '2';

  const { dataPoints, labelColors, title } = tooltipModel;
  let innerHTML = `
      <div className="magic-tooltip-wrapper">
      <div className="tooltip-date">${title[0].split('(')[0]}</div>
      `;
  dataPoints?.forEach((item, index) => {
    const parsedValue = JSON.parse(item.value);
    const value = parsedValue[1] - parsedValue[0];
    const quantity = (MAX_VALUE * value) / 100;

    const color = labelColors[index].backgroundColor;
    innerHTML += `
        <div className="magic-tooltip-info">
          <div className="magic-tooltip-visual">
            <span className="tooltip-graph-color" style="background-color: ${color}"></span>
             <p className="tooltip-graph-label"><span className="font-bold"> ${i18HumanReadableNumerals(
               quantity,
             )} users </span> of ${i18HumanReadableNumerals(MAX_VALUE)}
           </p>
          </div>
          <div className="tooltip-graph-value"> ${value.toFixed(2)}%</div>
        </div>
        `;
  });
  tooltipEl.innerHTML = `${innerHTML}</div>`;

  const tooltipHeight = tooltipEl.clientHeight;
  const { left: chartLeft, top: chartTop } = ctx._chart.canvas.getBoundingClientRect();

  const leftStyle = CENTER + chartLeft;
  const topStyle = chartTop + tooltipModel.caretY + window.pageYOffset - tooltipHeight - 10;

  tooltipEl.style.cssText = `
    position: absolute;
    opacity: 1;
    pointer-events: none;
    top: ${topStyle}px;
    left: ${leftStyle}px;
  `;
};

export const chartLabelPlugin = {
  id: 'chartLabel',
  afterDatasetsDraw(chart): void {
    const {
      data,
      ctx,
      chartArea: { left },
    } = chart;
    data.datasets.forEach((dataset, i) => {
      const meta = chart.getDatasetMeta(i);
      CENTER = (meta.data[0]._model.x - left) / 2 + left;
      meta.data.forEach((bar, index) => {
        const datapointPercentage = dataset.data[index][1] - dataset.data[index][0];
        const value = (datapointPercentage * MAX_VALUE) / 100;
        ctx.textAlign = 'center';
        ctx.font = 'bold 12px Lato';
        ctx.fillStyle = '#fff';
        ctx.fillText(`${i18HumanReadableNumerals(value)}`, CENTER, bar._model.y + 5);
      });
    });
  },
};
