import {
  getPast15DaysRange,
  getPastMonthRange,
  getPastWeekRange,
  getTodayRange,
  getYesterdayRange,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/Utils';

export const preDefinedDurations = [
  {
    label: 'Today',
    value: getTodayRange(),
  },
  {
    label: 'Yesterday',
    value: getYesterdayRange(),
  },
  {
    label: 'Past Week',
    value: getPastWeekRange(),
  },
  {
    label: 'Past 15 days',
    value: getPast15DaysRange(),
  },
  {
    label: 'Past Month',
    value: getPastMonthRange(),
  },
  {
    label: 'Past Quarter',
    value: getPastMonthRange(),
  },
];
