import { memo, useEffect, useState } from 'react';
import { Doughnut } from 'react-chartjs-2';
import { chartsDataFormatter } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FlaggedReasons/utils';

const chartOptions = {
  cutoutPercentage: 75,
  legend: {
    display: false,
  },
  tooltips: {
    enabled: false,
  },
  responsive: true,
  layout: {
    padding: {
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
    },
  },
};

const chartPlugins = [
  {
    beforeDraw: function beforeDraw(chart) {
      const width = chart.chart.width;
      const height = chart.chart.height;
      const ctx = chart.chart.ctx;

      ctx.restore();
      const fontSize = (height / 6).toFixed(2);
      ctx.font = `${fontSize}px lato`;
      ctx.fillStyle = 'black';
      ctx.textBaseline = 'middle';
      const textMain = `${chart.chart.config?.data?.datasets[0]?.data[0]}%`;
      const textMainX = Math.round((width - ctx.measureText(textMain).width) / 2);
      const textMainY = height * 0.42;
      ctx.fillText(textMain, textMainX, textMainY);
      ctx.save();

      ctx.restore();
      const subText = 'orders flagged';
      ctx.font = `${(height / 11).toFixed(2)}px lato`;
      ctx.fillStyle = 'grey';
      ctx.textBaseline = 'middle';
      const subTextX = Math.round((width - ctx.measureText(subText).width) / 2);
      const subTextY = height * 0.58;
      ctx.fillText(subText, subTextX, subTextY);
      ctx.save();
    },
  },
];

const DoughnutGroup = ({ reasonsList }) => {
  const [chartsData, setChartsData] = useState(null);

  useEffect(() => {
    setChartsData(chartsDataFormatter(reasonsList));
  }, [reasonsList, setChartsData]);

  return (
    <div className="charts-container">
      {chartsData?.map((chartData) => (
        <div key={chartData?.labels[0]} className="chart-container">
          <div className="doughnut-chart">
            <Doughnut data={chartData} options={chartOptions} plugins={chartPlugins} />
          </div>
          <span>{chartData?.labels[0]}</span>
        </div>
      ))}
    </div>
  );
};

export default memo(DoughnutGroup);
