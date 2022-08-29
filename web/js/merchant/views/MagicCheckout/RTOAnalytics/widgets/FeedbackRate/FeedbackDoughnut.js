import { useState, useEffect, memo } from 'react';
import { Doughnut } from 'react-chartjs-2';
import { feedbackDataFormatter } from 'merchant/views/MagicCheckout/RTOAnalytics/widgets/FeedbackRate/utils';

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
      if (!chart) return;

      const width = chart.chart?.width;
      const height = chart.chart?.height;
      const ctx = chart.chart?.ctx;

      ctx.restore();
      ctx.font = `${(height / 14).toFixed(2)}px lato`;
      ctx.fillStyle = 'grey';
      ctx.textBaseline = 'middle';
      const preText = 'Data shared for';
      const preTextX = Math.round((width - ctx.measureText(preText).width) / 2);
      const preTextY = height * 0.36;
      ctx.fillText(preText, preTextX, preTextY);
      ctx.save();

      ctx.restore();
      const fontSize = (height / 9).toFixed(2);
      ctx.font = `${fontSize}px lato`;
      ctx.fillStyle = 'black';
      ctx.textBaseline = 'middle';
      const textMain = `${chart.chart.config?.data?.datasets[0]?.data[0]}%`;
      const textMainX = Math.round((width - ctx.measureText(textMain).width) / 2);
      const textMainY = height * 0.52;
      ctx.fillText(textMain, textMainX, textMainY);
      ctx.save();

      ctx.restore();
      const subText = 'of all orders';
      ctx.font = `${(height / 14).toFixed(2)}px lato`;
      ctx.fillStyle = 'grey';
      ctx.textBaseline = 'middle';
      const subTextX = Math.round((width - ctx.measureText(subText).width) / 2);
      const subTextY = height * 0.66;
      ctx.fillText(subText, subTextX, subTextY);
      ctx.save();
    },
  },
];

const FeedbackDoughnut = ({ feedbackPercentage }) => {
  const [feedbackData, setFeedbackData] = useState(null);

  useEffect(() => {
    setFeedbackData(feedbackDataFormatter(feedbackPercentage));
  }, [setFeedbackData, feedbackPercentage]);

  return (
    <div className="chart-container">
      <div className="doughnut-chart">
        {feedbackData && (
          <Doughnut data={feedbackData} options={chartOptions} plugins={chartPlugins} />
        )}
      </div>
    </div>
  );
};

export default memo(FeedbackDoughnut);
