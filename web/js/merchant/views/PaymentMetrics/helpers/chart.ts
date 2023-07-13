import moment from 'moment';
import { GRAPHS_DATA, tagStyles, defaultTagStyle } from 'merchant/views/PaymentMetrics/constants';
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
  const methodDataList = getUniqueMethodOrInstrumentList(dataList, 'last_selected_method') || [];
  const datasets: Array<LineData> = [];
  methodDataList.forEach((method: string, ind: number) => {
    const color = tagStyles[ind] || defaultTagStyle.color;
    const data: Array<Record<string, string | number>> = [];
    dataList.forEach((val: Record<string, string | number>) => {
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
