import moment from 'moment';
import {
  GRAPHS_DATA,
  tagStyles,
  defaultTagStyle,
  CHART_NAME_MAP,
} from 'merchant/views/PaymentMetrics/constants';
import { getTimelineData } from './timelineData';
import { LineData } from 'merchant/views/PaymentMetrics//types';
/**
 * @returns '01 Nov 2022' if it is same day
 * @returns '01 - 07 Nov 2022' if it is same month
 * @returns '01 Oct 2022 - 30 Nov 2022' if it is lies on different months
 */

export const formatIntervals = ({ from, to }: { from: number; to: number }): string => {
  const isSameDay = moment(from).isSame(to, 'day');
  if (isSameDay) {
    return `${moment(from).format('DD MMM YYYY')}`;
  }
  return `${moment(from).format('DD MMM YYYY')} - ${moment(to).format('DD MMM YYYY')}`;
};

/**
 * @param time 1667304882000 in milliseconds
 * @returns '10:32 pm'
 */

export const formatTime = (time: number): string => {
  return moment(time).format('hh:mm a');
};

/** [{timestamp , last_selected_method , value } ...]
 * @returns ['upi' , 'card' , 'netbanking']
 */
export const getUniqueMethodOrInstrumentList = (
  list: Array<Record<string, string | number>>,
  key: string,
): string[] => {
  const methodList = new Set(list.map((data) => data[key]));
  const filteredList = Array.from(methodList) as string[];
  return filteredList.filter((value) => value !== 'null');
};

//  Method level split data for method level cr
export const methodLevelSplit = ({ dataList, lte, gte, breakdown }) => {
  // product wants to show wallets instead of wallet , can't change in backend
  const updatedDatList = dataList.map((data) => {
    if (data.last_selected_method === 'wallet') {
      data.last_selected_method = 'wallets';
    }
    return data;
  });

  // filtered 0 value data as card is coming with all 0 data for now
  const filteredData = updatedDatList.filter((data) => data.value);

  const methodDataList =
    getUniqueMethodOrInstrumentList(filteredData, 'last_selected_method') || [];
  const datasets: Array<LineData> = [];
  methodDataList.forEach((method: string, ind: number) => {
    const color = tagStyles[ind] || defaultTagStyle.color;
    const data: Array<Record<string, string | number>> = [];
    filteredData.forEach((val: Record<string, string | number>) => {
      if (val?.last_selected_method === method) {
        data.push(val as never);
      }
    });

    const finalData = getTimelineData({
      data,
      startTime: gte,
      endTime: lte,
      breakdown,
    });

    datasets.push({
      label: method.toUpperCase(),
      data: finalData,
      fill: false,
      borderWidth: 2,
      borderColor: color,
      backgroundColor: color,
      xAxisID: GRAPHS_DATA.METHOD_LEVEL_CR.xAxisID,
      yAxisID: GRAPHS_DATA.METHOD_LEVEL_CR.yAxisID,
      tagName: method,
    });
  });

  return datasets;
};

export const processOverallCrData = (
  result: Record<string, any>,
  gte: number,
  lte: number,
  breakdown: string,
  type: string,
) => {
  try {
    let data = (result.data && result.data[CHART_NAME_MAP[type]]?.result) || [];

    if (data.length) {
      // get missing timestamp data as well as from backend if value 0 they are not sending
      // but for chart to get staring line we need to plot 0 otherwise it will act as dot
      data = getTimelineData({ data, startTime: gte, endTime: lte, breakdown });

      return [
        {
          label: GRAPHS_DATA[type].name,
          data,
          fill: true,
          borderWidth: 2,
          borderColor: defaultTagStyle.color,
          pointBackgroundColor: defaultTagStyle.color,
          xAxisID: GRAPHS_DATA[type].xAxisID,
          yAxisID: GRAPHS_DATA[type].yAxisID,
          tagName: GRAPHS_DATA[type].name,
          backgroundColor: (context) => {
            const chart = context.chart;
            const { ctx, chartArea } = chart;

            if (!chartArea || !chartArea.bottom || !chartArea.top) {
              // This case happens on initial chart load
              return defaultTagStyle.backgroundColor1;
            }
            const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);

            gradient?.addColorStop(1, defaultTagStyle.backgroundColor1);
            gradient?.addColorStop(0.2, defaultTagStyle.backgroundColor2);

            return gradient;
          },
        },
      ];
    }
    return [];
  } catch {
    return [];
  }
};
