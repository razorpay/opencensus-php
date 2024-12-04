import moment from 'moment';

import { getFromDate } from 'merchant/views/Transactions/v1/Payments/components/JKBankTransactions/helper';
import {
  TODAY,
  LAST_7_DAYS,
  LAST_30_DAYS,
  LAST_90_DAYS,
  CURRENT_YEAR_JAN_TILL_DATE,
  THIS_FINANCIAL_YEAR,
} from 'merchant/views/Transactions/v1/Payments/components/JKBankTransactions/constants';

jest.mock('moment', () => {
  const originalMoment = jest.requireActual('moment');
  return jest.fn(() => originalMoment('2023-01-01T00:00:00.000Z')); // Default mock date
});
describe('getFromDate', () => {
  test('should return start of today for TODAY preset', () => {
    const fromDate = getFromDate(TODAY);
    expect(fromDate).toEqual(moment().startOf('day').toDate());
  });

  test('should return start of 7 days ago for LAST_7_DAYS preset', () => {
    const fromDate = getFromDate(LAST_7_DAYS);
    expect(fromDate).toEqual(moment().subtract(7, 'days').startOf('day').toDate());
  });

  test('should return start of 30 days ago for LAST_30_DAYS preset', () => {
    const fromDate = getFromDate(LAST_30_DAYS);
    expect(fromDate).toEqual(moment().subtract(30, 'days').startOf('day').toDate());
  });

  test('should return start of 90 days ago for LAST_90_DAYS preset', () => {
    const fromDate = getFromDate(LAST_90_DAYS);
    expect(fromDate).toEqual(moment().subtract(90, 'days').startOf('day').toDate());
  });

  test('should return start of the year for CURRENT_YEAR_JAN_TILL_DATE preset', () => {
    const fromDate = getFromDate(CURRENT_YEAR_JAN_TILL_DATE);
    expect(fromDate).toEqual(moment().startOf('year').toDate());
  });
  test('should return start of April for THIS_FINANCIAL_YEAR preset', () => {
    // Mock date in Q1
    (moment as unknown as jest.Mock).mockImplementation(() =>
      jest.requireActual('moment')('2023-01-01T00:00:00.000Z'),
    );
    let fromDate = getFromDate(THIS_FINANCIAL_YEAR);
    expect(fromDate).toEqual(moment().subtract(1, 'year').month('April').startOf('month').toDate());

    // Mock date not in Q1
    (moment as unknown as jest.Mock).mockImplementation(() =>
      jest.requireActual('moment')('2023-05-01T00:00:00.000Z'),
    );
    fromDate = getFromDate(THIS_FINANCIAL_YEAR);
    expect(fromDate).toEqual(moment().month('April').startOf('month').toDate());
  });
});
