import moment from 'moment';

export const getFormattedDate = (value) => {
  return moment(value).format('DD/MM/YYYY hh:mm A'); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
};
