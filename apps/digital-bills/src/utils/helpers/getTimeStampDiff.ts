import moment from 'moment';

const getTimeStampDiff = (
  timeStampA: string,
  timeStampB: string,
  unitOfTime: moment.unitOfTime.Diff = 'milliseconds',
): number => moment(timeStampA).diff(moment(timeStampB), unitOfTime);

export default getTimeStampDiff;
