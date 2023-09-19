import moment from 'moment';
import { getLabels } from 'merchant/views/MagicCheckout/RTOAnalytics/utils';
import { BREAKDOWN_MAP } from 'merchant/views/MagicCheckout/RTOAnalytics/constants';

interface DataObject {
  period?: number;
  total_rto_rate?: number;
  rto_rate?: number;
}

const PreAndPostMagicDefaultOptions = {
  responsive: true,
  scales: {
    xAxes: [
      {
        type: 'category',
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
            return `${value}%`;
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
    enabled: true,
    backgroundColor: '#ffffff',
    borderColor: '#e0e8f4',
    borderWidth: 1,
    bodySpacing: 12,
    position: 'average',
    bodyFontColor: '#262D3A',
    footerFontColor: '#8A91AC',
    xPadding: 12,
    yPadding: 12,
    cornerRadius: 2,
    footerMarginTop: 14,
    footerFontStyle: 'normal',
    callbacks: {
      title() {},
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
} as {
  [x: string]: any;
};

export const getPostMagicOrderData = (
  rawData: { postmagic_rto_rate?: DataObject[] },
  breakdown: string,
  startMoment: moment.Moment,
  labels: string[],
) => {
  const PostMagicOrders = {
    label: 'RTO rate with Magic Checkout',
    data: [] as (number | null)[],
    tension: 0,
    borderColor: '#5B4EAE',
    fill: 'start',
    borderWidth: 1,
    pointBackgroundColor: '#5B4EAE',
    backgroundColor: (context) => {
      const chart = context.chart;
      const { ctx, chartArea } = chart;

      if (!chartArea) {
        // This case happens on initial chart load
        return null;
      }
      const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
      gradient.addColorStop(0.7, '#DED9FF');
      gradient.addColorStop(0, '#FEFFFE');

      return gradient;
    },
  };

  let prevTimestamp: number;
  let indexToDelete = 0;

  //adding a null value so that not data point will be ploted against the first period of the selected date range as we only want to show Premagic data
  PostMagicOrders.data.push(null);

  rawData?.postmagic_rto_rate?.forEach((dataObj: DataObject) => {
    const timestamp = moment(Number(dataObj.period) * 1000)
      .startOf(breakdown === 'weekly' ? 'isoWeek' : BREAKDOWN_MAP[breakdown]?.value)
      .toDate()
      .getTime();

    if (!prevTimestamp) {
      prevTimestamp = startMoment.clone().toDate().getTime();
      if (prevTimestamp < timestamp) {
        // this is the timestamp for the very first non-empty entry we get for a period
        // within the selected date range

        delete labels[indexToDelete];
        indexToDelete++;
      }
    }

    let numPointsGap = Math.abs(
      moment(timestamp).diff(prevTimestamp, BREAKDOWN_MAP[breakdown]?.value),
    );
    // filling in the gaps for periods b/w date range with no BE data
    while (numPointsGap > 1) {
      delete labels[indexToDelete];
      indexToDelete++;
      numPointsGap--;
    }

    PostMagicOrders.data.push(dataObj.total_rto_rate ?? 0);
    indexToDelete++;

    prevTimestamp = timestamp;
  });

  return PostMagicOrders;
};
export const getPreMagicOrderData = (widgetData: {
  premagic_rto_rate?: DataObject;
  postmagic_rto_rate?: DataObject[];
}) => {
  const PreMagicOrders = {
    label: 'Previous RTO rate',
    data: [] as (number | null)[],
    tension: 0,
    borderColor: '#E86250',
    fill: 'start',
    borderWidth: 1,
    pointBackgroundColor: '#E86250',
    backgroundColor: (context) => {
      const chart = context.chart;
      const { ctx, chartArea } = chart;

      if (!chartArea) {
        // This case happens on initial chart load
        return null;
      }
      const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);

      gradient.addColorStop(0.7, '#FFEFED');
      gradient.addColorStop(0, '#FEFFFE');

      return gradient;
    },
  };

  PreMagicOrders.data.push(widgetData?.premagic_rto_rate?.rto_rate || 0);

  //pushing the first non empty data point of postmagic rto rate inorder to build a connecting line chart between premagic rto rate and postmagic rto rate
  PreMagicOrders.data.push(widgetData?.postmagic_rto_rate?.[0]?.total_rto_rate || 0);

  return PreMagicOrders;
};
export const preAndPostMagicFormatter = (rawData, breakdown, startTime, endTime) => {
  const startMoment = moment(startTime).startOf(
    breakdown === 'weekly' ? 'isoWeek' : BREAKDOWN_MAP[breakdown]?.value,
  );

  const labels = getLabels(startTime, endTime, breakdown);

  const PreMagicOrders = getPreMagicOrderData(rawData);
  const PostMagicOrders = getPostMagicOrderData(rawData, breakdown, startMoment, labels);

  //removing the labels for which no data point is available
  let newLabels = labels.filter((item: number | undefined) => item !== undefined);
  newLabels.unshift('Previous RTO rate');

  const totalOrderData = PreMagicOrders.data.length + PostMagicOrders.data.length - 1;

  if (totalOrderData < newLabels.length) {
    newLabels = newLabels.slice(0, totalOrderData);
  }
  return { labels: newLabels, datasets: [PreMagicOrders, PostMagicOrders] };
};

export function getPreAndPostMagicChartOptions(breakdown) {
  const options = { ...PreAndPostMagicDefaultOptions };
  const {
    tooltips,
    scales: { xAxes },
  } = options;

  xAxes[0].ticks.callback = (_value, index, values) => {
    if (index === 0) {
      return values[index];
    }
    const currVal = moment(values[index]);

    return currVal.format('MMM');
  };

  tooltips.callbacks = {
    ...tooltips.callbacks,
    label: (tooltipItem, { datasets }) => {
      const { index, datasetIndex, yLabel } = tooltipItem;

      if (index === 1 && datasetIndex === 0) {
        return null;
      }
      const label = datasets[datasetIndex]?.label;
      return `${label}: ${yLabel}%`;
    },
    footer([tooltipItem]) {
      let displayLabel;
      const breakdownValue = BREAKDOWN_MAP[breakdown]?.value;
      const label = tooltipItem?.xLabel;
      //incase of previous rto rate don't want to show the time range
      if (label === 'Previous RTO rate') {
        return null;
      }
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
    labelColor: (item, chart) => {
      const color = chart?.config?.data?.datasets[item.datasetIndex]?.borderColor;
      return { backgroundColor: color, borderColor: 'transparent' };
    },
  };

  return options;
}
