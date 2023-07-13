import {
  initialFilters,
  getBreakdownInterval,
  validateDateRange,
  formatIntervals,
  formatTime,
  getUniqueMethodOrInstrumentList,
  methodLevelSplit,
  getOverallCRData,
  getMethodLevelCRData,
  getTimeForSelectedGraphs,
} from 'merchant/views/PaymentMetrics/helpers';
import { getPaymentMetricsData } from 'merchant/views/PaymentMetrics/service';
import { DEFAULT_PRESET, PRESETS } from 'merchant/views/PaymentMetrics/constants';
import moment from 'moment';
import { Filter } from 'merchant/views/PaymentMetrics/types';

jest.mock('merchant/views/PaymentMetrics/service', () => ({
  getPaymentMetricsData: jest.fn(),
}));

describe('Tests for #initialfilters', () => {
  test('should return default preset and current ednTime with 6 hour back startTime', () => {
    const result = initialFilters();
    const endDate = moment().endOf('hour') as moment.Moment;
    let startDate = endDate.clone() as moment.Moment;
    startDate = startDate.subtract(6, 'hours').startOf('hour');
    expect(result).toMatchObject({ startDate, endDate, preset: PRESETS[DEFAULT_PRESET] });
  });
});

const testsForGetBreakdownInterval = [
  {
    title: 'should return hourly if less than 1 days',

    input: {
      from: moment().subtract(6, 'hours'),
      to: moment(),
    },
    output: 'hourly',
  },
  {
    title: 'should return hourly if less than 2 days',
    input: {
      from: moment().subtract(23, 'hours'),
      to: moment(),
    },
    output: 'hourly',
  },
  {
    title: 'should return daily if more than 2 days and less than 14 days',
    input: {
      from: moment().subtract(4, 'days'),
      to: moment(),
    },
    output: 'daily',
  },
  {
    title: 'should return weekly if more than 14 days',
    input: {
      from: moment().subtract(20, 'days'),
      to: moment(),
    },
    output: 'weekly',
  },
];

describe('Test getBreakdownInterval', () => {
  test.each(testsForGetBreakdownInterval)('#getBreakdownInterval ($title)', ({ input, output }) => {
    const result = getBreakdownInterval(input.from, input.to);
    expect(result).toEqual(output);
  });
});

const testsForValidateDateRange = [
  {
    title: 'should return error start Date is required if no start date',
    input: {
      startDate: '',
      endDate: moment(),
    },
    output: { date: 'Start date is required' },
  },
  {
    title: 'should return error start Date is required if no end data',
    input: {
      startDate: moment(),
      endDate: '',
    },
    output: { date: 'End date is required' },
  },
  {
    title:
      'should return error Start date cannot be greater than end date when start date is greater',
    input: {
      startDate: moment(),
      endDate: moment().subtract(6, 'hours'),
    },
    output: { date: 'Start date cannot be greater than end date' },
  },
  {
    title:
      'should return error End time cannot be greater than current time when end date is greater than current',
    input: {
      startDate: moment().subtract(6, 'hours'),
      endDate: moment().add(2, 'hours'),
    },
    output: { date: 'End time cannot be greater than current time' },
  },
  {
    title:
      'should return error Please select a minimum range of 6 hours when start date is greater',
    input: {
      startDate: moment().subtract(4, 'hours'),
      endDate: moment(),
    },
    output: { date: 'Please select a minimum range of 6 hours' },
  },
  {
    title: 'should not return any error if everything is fine',
    input: {
      startDate: moment().subtract(8, 'hours'),
      endDate: moment(),
    },
    output: {},
  },
];

describe('Test validateDateRange', () => {
  test.each(testsForValidateDateRange)('#validateDateRange ($title)', ({ input, output }) => {
    const errors = validateDateRange(input as unknown as Filter);
    expect(errors).toMatchObject(output);
  });
});

describe('Tests for #formatIntervals', () => {
  test('should return in DD MMM YYYY when same day', () => {
    const result = formatIntervals({ from: 1688387400000, to: 1688391000000 });
    expect(result).toBe('03 Jul 2023');
  });
  test('should return in DD MMM YYYY - DD MMM YYYY when different day', () => {
    const result = formatIntervals({ from: 1687804200000, to: 1687890600000 });
    expect(result).toBe('26 Jun 2023 - 27 Jun 2023');
  });
});

describe('Tests for #formatTime', () => {
  test('should return in hh:mm with am', () => {
    const result = formatTime(1688187400000);
    expect(result).toBe('04:56 am');
  });
  test('should return in hh:mm with pm', () => {
    const result = formatTime(1687804200000);
    expect(result).toBe('06:30 pm');
  });
});

describe('Tests for #getUniqueMethodOrInstrumentList', () => {
  test('should return all unique methods', () => {
    const result = getUniqueMethodOrInstrumentList(
      [
        { last_selected_method: 'card' },
        { last_selected_method: 'card' },
        { last_selected_method: 'upi' },
        { last_selected_method: 'netbanking' },
      ],
      'last_selected_method',
    );
    expect(result).toEqual(['card', 'upi', 'netbanking']);
  });
  test('should return all unique instruments', () => {
    const result = getUniqueMethodOrInstrumentList(
      [
        { last_selected_instrument: 'SBIN' },
        { last_selected_instrument: 'ICIC' },
        { last_selected_instrument: 'HDFC' },
        { last_selected_instrument: 'SBIN' },
      ],
      'last_selected_instrument',
    );
    expect(result).toEqual(['SBIN', 'ICIC', 'HDFC']);
  });
});

describe('Tests for #methodLevelSplit', () => {
  test('should return all data for method cr chart to be consumed for 6 hours', () => {
    const result = methodLevelSplit({
      dataList: [
        {
          last_selected_method: 'upi',
          timestamp: 1688394600,
          value: 100,
        },
        {
          last_selected_method: 'wallet',
          timestamp: 1688383800,
          value: 100,
        },
        {
          last_selected_method: 'card',
          timestamp: 1688383800,
          value: 20,
        },
        {
          last_selected_method: 'null',
          timestamp: 1688401800,
          value: 0,
        },
        {
          last_selected_method: 'null',
          timestamp: 1688405400,
          value: 0,
        },
        {
          last_selected_method: 'upi',
          timestamp: 1688398200,
          value: 90.56603773584905,
        },
        {
          last_selected_method: 'netbanking',
          timestamp: 1688383800,
          value: 95.83333333333333,
        },
      ],
      lte: 1688408999,
      gte: 1688383800,
      breakdown: 'hourly',
    });
    expect(result.length).toBe(4);
  });

  test('should return all data for method cr chart to be consumed for daily for 7 days', () => {
    const result = methodLevelSplit({
      dataList: [
        {
          last_selected_method: 'card',
          timestamp: 1687890600,
          value: 85.87570621468926,
        },
        {
          last_selected_method: 'cod',
          timestamp: 1688063400,
          value: 100,
        },
        {
          last_selected_method: 'card',
          timestamp: 1687804200,
          value: 88.88888888888889,
        },
        {
          last_selected_method: 'paylater',
          timestamp: 1688322600,
          value: 66.66666666666667,
        },

        {
          last_selected_method: 'emi',
          timestamp: 1687804200,
          value: 100,
        },
      ],
      lte: 1688466599,
      gte: 1687858200,
      breakdown: 'daily',
    });
    expect(result.length).toBe(4);
  });
});

describe('Tests for #getOverallCRData', () => {
  test('should call getPaymentMetricsData function', async () => {
    const mockedError = {
      code: undefined,
      errors: [''],
    };

    (getPaymentMetricsData as jest.Mock).mockRejectedValue(mockedError);

    await expect(getOverallCRData({ lte: 12345678, gte: 123987654 })).rejects.toEqual(mockedError);
  });
});

describe('Tests for #getMethodLevelCRData', () => {
  test('should call getPaymentMetricsData function', async () => {
    const mockedError = {
      code: undefined,
      errors: [''],
    };

    (getPaymentMetricsData as jest.Mock).mockRejectedValue(mockedError);

    await expect(getMethodLevelCRData({ lte: 12345678, gte: 123987654 })).rejects.toEqual(
      mockedError,
    );
  });
});

describe('Tests for #getTimeForSelectedGraphs', () => {
  test('should return custom preset with Jul 1 data and jul2 data 11am', () => {
    const time = 'Jul 1, 2023, 11:00:00 am';
    const result = getTimeForSelectedGraphs(time, 'hourly');

    expect(result).toEqual({
      startDate: moment(time).startOf('hour'),
      preset: PRESETS[PRESETS.length - 1],
      endDate: moment(time).add(1, 'days'),
    });
  });

  test('should return custom preset with daily data of single day start and end time of that day', () => {
    const time = 'Jul 1, 2023, 11:00:00 am';
    const result = getTimeForSelectedGraphs(time, 'daily');

    expect(result).toEqual({
      startDate: moment(time).startOf('day'),
      preset: PRESETS[PRESETS.length - 1],
      endDate: moment(time).endOf('day'),
    });
  });

  test('should return custom preset with daily data of a week start and End date', () => {
    const time = 'Jun 1, 2023, 11:00:00 am';
    const result = getTimeForSelectedGraphs(time, 'weekly');

    expect(result).toEqual({
      startDate: moment(time).startOf('day'),
      preset: PRESETS[PRESETS.length - 1],
      endDate: moment(time).add(7, 'days').endOf('day'),
    });
  });
});
