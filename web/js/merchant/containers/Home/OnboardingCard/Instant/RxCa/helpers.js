export const getTimeDiff = (timeStamp, noOfDays) => {
  return moment.unix(timeStamp).add(noOfDays, 'days').diff(moment(), 'days');
};
