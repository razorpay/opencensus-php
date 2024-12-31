import moment from 'moment';

export const pastDate = (interval: string): string =>
  moment().subtract(interval, 'days').toISOString();

export const currDate = (): string => moment().toISOString();
