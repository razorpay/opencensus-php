import moment from 'moment';
import { humanReadableIndianCurrency } from 'common/utils/numerals';
import { getLabels } from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import { BREAKDOWN_MAP } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

const costSavedDefaultOptions = {
  responsive: true,
  scales: {
    xAxes: [
      {
        type: 'time',
        distribution: 'series',
        time: {
          displayFormats: {
            hour: 'MMM D',
            month: 'MMM YYYY',
            day: 'MMM D',
            week: 'MMM YYYY',
            second: 'MMM D',
            millisecond: 'MMM D',
          },
          tooltipFormat: 'ddd DD MMM YYYY',
        },
        offset: true,
        gridLines: {
          offsetGridLines: true,
          display: true,
          drawOnChartArea: false,
          drawTicks: true,
        },
        ticks: {
          autoSkip: true,
          fontSize: 12,
          fontColor: '#858C9A',
          maxRotation: 0,
          autoSkipPadding: 15,
          labelOffset: 15,
        },
      },
    ],
    yAxes: [
      {
        ticks: {
          beginAtZero: true,
          padding: 10,
          fontSize: 12,
          maxTicksLimit: 5,
          fontColor: '#858C9A',
          callback: (value) => {
            return humanReadableIndianCurrency(value);
          },
        },
        gridLines: {
          color: '#F1F3F6',
          zeroLineColor: '#E0E8F4',
          display: true,
          drawTicks: false,
          drawBorder: false,
        },
      },
    ],
  },
  tooltips: {
    backgroundColor: '#ffffff',
    borderColor: '#e0e8f4',
    borderWidth: 1,
    footerFontStyle: '400',
    bodySpacing: 2,
    position: 'nearest',
    displayColors: false,
    titleMarginBottom: 2,
    titleFontColor: '#262D3A',
    footerFontColor: '#8A91AC',
    xPadding: 12,
    yPadding: 12,
    cornerRadius: 2,
    callbacks: {
      title: (item) => {
        return `Cost saved due to RTO      ${humanReadableIndianCurrency(item[0].yLabel)}`;
      },
    },
  },
  layout: {
    padding: {
      top: 24,
      left: 24,
      right: 24,
      bottom: 0,
    },
  },
};

export const costSavedFormatter = (rawData, breakdown, startTime, endTime) => {
  const startMoment = moment(startTime).startOf(
    breakdown === 'weekly' ? 'isoWeek' : BREAKDOWN_MAP[breakdown]?.value,
  );

  let labels = getLabels(startTime, endTime, breakdown);
  const costSavedOrders = {
    label: 'Cost Saved',
    data: [],
    tension: 0,
    borderColor: '#7EB471',
    fill: 'start',
    borderWidth: 1,
    pointBackgroundColor: '#7EB471',
    backgroundColor: (context) => {
      const chart = context.chart;
      const { ctx, chartArea } = chart;

      if (!chartArea) {
        // This case happens on initial chart load
        return null;
      }
      const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
      gradient.addColorStop(0.7, '#F8FCF8');
      gradient.addColorStop(0, '#FEFFFE');

      return gradient;
    },
  };

  let prevTimestamp;

  rawData?.forEach((dataObj) => {
    const timestamp = moment(Number(dataObj.period) * 1000)
      .startOf(breakdown === 'weekly' ? 'isoWeek' : BREAKDOWN_MAP[breakdown]?.value)
      .toDate()
      .getTime();

    if (!prevTimestamp) {
      prevTimestamp = startMoment.clone().toDate().getTime();
      if (prevTimestamp < timestamp) {
        // this is the timestamp for the very first non-empty entry we get for a period
        // within the selected date range

        costSavedOrders.data.push(0);
      }
    }

    let numPointsGap = Math.abs(
      moment(timestamp).diff(prevTimestamp, BREAKDOWN_MAP[breakdown]?.value),
    );
    // filling in the gaps for periods b/w date range with no BE data
    while (numPointsGap > 1) {
      costSavedOrders.data.push(0);
      numPointsGap--;
    }

    costSavedOrders.data.push(dataObj.shipping_charges ?? 0);

    prevTimestamp = timestamp;
  });
  // for when the tail end of the data is empty
  if (costSavedOrders.data.length < labels.length) {
    labels = labels.slice(0, costSavedOrders.data.length);
  }

  return { labels, datasets: [costSavedOrders] };
};

export function getCostSavedChartOptions(breakdown, isManualReviewOpted) {
  const options = { ...costSavedDefaultOptions };
  const {
    tooltips,
    scales: { xAxes },
  } = options;

  xAxes[0].ticks.callback = (_value, index, values) => {
    const currVal = moment(values[index].value);
    let format = 'MMM DD';

    if (breakdown === 'monthly') {
      format = 'MMM';
    }

    return currVal.format(format);
  };

  tooltips.callbacks = {
    ...tooltips.callbacks,
    title: (item) => {
      if (isManualReviewOpted) {
        return `Cost saved due to review     ${humanReadableIndianCurrency(item[0].yLabel)}`;
      }
      return `Cost saved due to RTO      ${humanReadableIndianCurrency(item[0].yLabel)}`;
    },
    footer([tooltipItem]) {
      let displayLabel;
      const breakdownValue = BREAKDOWN_MAP[breakdown]?.value;
      const label = tooltipItem?.xLabel;
      const labelStartMoment = moment(label);
      const labelEnd = labelStartMoment
        .clone()
        .endOf(breakdown === 'weekly' ? 'isoWeek' : breakdownValue);
      let format = 'ddd DD MMM YYYY';

      if (breakdown === 'weekly' || breakdown === 'monthly') {
        format = 'MMM DD YYYY';
        displayLabel = `${labelStartMoment.format(format)} - ${labelEnd.format(format)}`;
      } else {
        displayLabel = labelStartMoment.format(format);
      }

      return displayLabel;
    },
  };

  return options;
}
