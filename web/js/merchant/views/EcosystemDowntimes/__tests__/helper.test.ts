import {
  getDowntimesAfterTimestamp,
  getHoursMinutesFromTimestamp,
  getRemainingTime,
  processOnGoingDowntimes,
  processPreviousDowntimes,
  getSupportedMethodInstrumentDictionary,
  getDowntimeHeaderAndDescription,
  groupAllDowntimesByMethod,
  getInstrumentList,
  _prepareDowntimeObj,
} from 'merchant/views/EcosystemDowntimes/helpers';
import {
  downtime_mock_response,
  high_sev_downtime_mock,
  previous_downtimes_mock,
} from './mocks/mockResponses';
import moment from 'moment';
import * as constants from 'merchant/views/EcosystemDowntimes/constants';

describe('Helpers', () => {
  test('should return with correct data model when processOnGoingDowntimes called with ongoing response', () => {
    const { data: ongoingMockResponse } = downtime_mock_response;
    const downtimeRespStruct = {
      id: expect.any(String),
      instrument: expect.any(Object),
    };
    expect(processOnGoingDowntimes(ongoingMockResponse)).toMatchObject({
      card: {
        network: {
          VISA: downtimeRespStruct,
        },
      },
      upi: {
        vpa_handle: {
          okaxis: downtimeRespStruct,
        },
      },
    });
  });

  test('should return with empty object when processOnGoingDowntimes called no data', () => {
    const { data: ongoingMockResponse } = { data: [] };
    expect(processOnGoingDowntimes(ongoingMockResponse)).toMatchObject({});
    expect(processOnGoingDowntimes()).toMatchObject({});
  });

  test('should return with correct data model when processPreviousDowntimes called with ongoing response', () => {
    const { data: previousDowntimeMockResp } = downtime_mock_response;
    const downtimeRespStruct = {
      previousDowntimes: expect.any(Array),
    };
    expect(processPreviousDowntimes(previousDowntimeMockResp)).toMatchObject({
      card: {
        network: {
          VISA: downtimeRespStruct,
        },
      },
      upi: {
        vpa_handle: {
          okaxis: downtimeRespStruct,
        },
      },
    });
  });

  test('should return with empty object when processPreviousDowntimes called no data', () => {
    const { data: previousDowntimes } = { data: [] };
    expect(processPreviousDowntimes(previousDowntimes)).toMatchObject({});
    expect(processPreviousDowntimes()).toMatchObject({});
  });

  test('should return with correct mins and secs left when getRemainingTime is called with seconds', () => {
    expect(getRemainingTime(30)).toBe('30s');
    expect(getRemainingTime(130)).toBe('2m 10s');
  });

  test('getDowntimesAfterTimestamp should return correct totalDuration and totalDowntimes', () => {
    jest.useFakeTimers('modern').setSystemTime(new Date(2023, 1, 3));

    const pastDowntimes = processPreviousDowntimes(previous_downtimes_mock.data);
    const pastDowntimesForMockInstrument = pastDowntimes.netbanking.bank.ICIC.previousDowntimes;
    const response = getDowntimesAfterTimestamp({
      pastDowntimesForInstrument: pastDowntimesForMockInstrument,
      timestamp: moment(new Date(2023, 1, 3)).startOf('day').unix(),
    });
    expect(response.totalDuration).toBe('4mins');
  });

  test('getDowntimesAfterTimestamp should return with 0 if no active/past downtimes', () => {
    const response = getDowntimesAfterTimestamp({
      pastDowntimesForInstrument: [],
      timestamp: moment(new Date()).startOf('day').unix(),
    });
    expect(response.totalDowntimes).toBe(0);
    expect(response.totalDuration).toBe(0);
  });

  test('getHoursMinutesFromTimestamp should return correct total duration string', () => {
    expect(getHoursMinutesFromTimestamp(300, [])).toBe('5mins');
    expect(getHoursMinutesFromTimestamp(322, [])).toBe('5mins 22secs');
    expect(getHoursMinutesFromTimestamp(322, ['secs'])).toBe('5mins');
  });

  test('getSupportedMethodInstrumentDictionary should return with correct method-instrument struct', () => {
    const response = getSupportedMethodInstrumentDictionary();
    expect(response.card?.issuer?.CNRB).toBe(true);
  });

  test('getDowntimeHeaderAndDescription should return correct header and description for summary', () => {
    const downtime = high_sev_downtime_mock;
    const response = getDowntimeHeaderAndDescription(downtime);
    expect(response.heading).toBe('High Severity Downtime');
    expect(response.description).toBe('VISA (started at 09/01, 11:31)');
    expect(typeof response.icon).toBe('object');
  });

  test('groupAllDowntimesByMethod should group by method provided', () => {
    const activeDowntimes = processOnGoingDowntimes(downtime_mock_response.data);
    const response = groupAllDowntimesByMethod(activeDowntimes, 'card');
    expect(response.length).toBe(2);
  });

  test('groupAllDowntimesByMethod should return empty if invalid method provided', () => {
    const activeDowntimes = processOnGoingDowntimes(downtime_mock_response.data);
    const response = groupAllDowntimesByMethod(activeDowntimes, 'invalid');
    expect(response.length).toBe(0);
  });

  test('getInstrumentList should group downtimes by method', () => {
    const spy = jest.spyOn(constants, 'accessInstrumentList');
    spy.mockReturnValue([
      { method: 'card', issuer: 'BKID' },
      { method: 'card', network: 'DICL' },
      { method: 'upi' },
    ]);

    const list = getInstrumentList();
    const methods = Object.keys(list);
    expect(methods.length).toBe(1);

    spy.mockRestore();
  });

  test('_prepareDowntimeObj', () => {
    const current = {
      card: {
        network: {
          rupay: {},
        },
      },
    };

    const response1 = _prepareDowntimeObj({
      currentDowntimeObj: current,
      method: 'upi',
      instrumentType: 'vpa_handle',
      instrumentValue: 'apl',
      data: 'mockData',
    });

    expect(response1?.upi?.vpa_handle?.apl).toBe('mockData');

    const response2 = _prepareDowntimeObj({
      currentDowntimeObj: current,
      method: 'card',
      instrumentType: 'issuer',
      instrumentValue: 'sbi',
      data: 'mockData',
    });

    expect(response2?.card?.issuer?.sbi).toBe('mockData');

    const response3 = _prepareDowntimeObj({
      currentDowntimeObj: current,
      method: 'card',
      instrumentType: 'network',
      instrumentValue: 'visa',
      data: 'mockData',
    });

    expect(response3?.card?.network?.visa).toBe('mockData');
  });
});
