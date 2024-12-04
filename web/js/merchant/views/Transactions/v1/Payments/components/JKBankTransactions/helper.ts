import moment from 'moment';
import {
  TODAY,
  LAST_7_DAYS,
  LAST_30_DAYS,
  LAST_90_DAYS,
  CURRENT_YEAR_JAN_TILL_DATE,
  THIS_FINANCIAL_YEAR,
} from './constants';

export const groupItemsByDate = (items) => {
  const groupedData = items.reduce((acc, item) => {
    const date =
      typeof item.created_at === 'string' ? moment(item.created_at) : moment.unix(item.created_at);
    const formatedDate = date.format('ll');
    if (!acc[formatedDate]) {
      acc[formatedDate] = [];
    }
    acc[formatedDate].push(item);
    return acc;
  }, {});

  return groupedData;
};

export const getFromDate = (preset) => {
  switch (preset) {
    case TODAY:
      return moment().startOf('day').toDate();
    case LAST_30_DAYS:
      return moment().subtract(30, 'days').startOf('day').toDate();
    case LAST_90_DAYS:
      return moment().subtract(90, 'days').startOf('day').toDate();
    case CURRENT_YEAR_JAN_TILL_DATE:
      return moment().startOf('year').toDate();
    case THIS_FINANCIAL_YEAR:
      if (moment().quarter() === 1) {
        return moment().subtract(1, 'year').month('April').startOf('month').toDate();
      }
      return moment().month('April').startOf('month').toDate();
    case LAST_7_DAYS:
    default:
      return moment().subtract(7, 'days').startOf('day').toDate();
  }
};
