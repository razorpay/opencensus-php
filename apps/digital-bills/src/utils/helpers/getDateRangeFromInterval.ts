import moment from 'moment';

export const pastDate = (interval: string): string =>
  moment().subtract(interval, 'days').startOf('day').toISOString();

export const currDate = (): string => moment().endOf('day').toISOString();
