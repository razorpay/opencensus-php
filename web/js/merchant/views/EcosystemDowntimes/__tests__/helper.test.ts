import {
  getDowntimesAfterTimestamp,
  getElapsedTime,
  getRemainingTime,
  processOnGoingDowntimes,
  processPreviousDowntimes,
  getSupportedMethodInstrumentDictionary,
  getDowntimeHeaderAndDescription,
  groupAllDowntimesByMethod,
  getInstrumentList,
  _prepareDowntimeObj,
  getSrDatPointsFromResponse,
  extractInstrumentType,
} from 'merchant/views/EcosystemDowntimes/helpers';
import {
  downtime_mock_response,
  failed_sr_mock_response,
  high_sev_downtime_mock,
  previous_downtimes_mock,
  sr_mock_response,
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
    const pastDowntimesForMockInstrument = pastDowntimes?.netbanking?.bank?.ICIC;
    const response = getDowntimesAfterTimestamp({
      pastDowntimesForInstrument: pastDowntimesForMockInstrument?.previousDowntimes,
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

  test('getElapsedTime should return correct total duration string', () => {
    expect(getElapsedTime(300)).toBe('5mins');
    expect(getElapsedTime(2032)).toBe('34mins');
    expect(getElapsedTime(144)).toBe('2mins');
    expect(getElapsedTime(12)).toBe('12secs');
    expect(getElapsedTime(60)).toBe('1min');
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
      { method: 'card', issuer: 'BKID', srKey: 'card.issuer.BKID' },
      { method: 'card', network: 'DICL', srKey: 'card.network.Diners Club' },
      { method: 'upi', srKey: null },
    ]);

    const list = getInstrumentList({ activeDowntimes: null });
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

  test('getInstrumentList should return instruments in correct order', () => {
    const spy = jest.spyOn(constants, 'accessInstrumentList');
    spy.mockReturnValue([
      { method: 'card', network: 'VISA', srKey: 'card.network.Visa' },
      { method: 'card', network: 'AMEX', srKey: 'card.network.American Express' },
      { method: 'card', network: 'DICL', srKey: 'card.network.Diners Club' },
      { method: 'upi', srKey: null },
      { method: 'card', network: 'RUPAY', srKey: 'card.network.Rupay' },
    ]);

    const response = getInstrumentList({
      activeDowntimes: processOnGoingDowntimes(downtime_mock_response.data),
    });

    const cardsNetworkInstruments = response.card?.network;
    expect(cardsNetworkInstruments?.[0]?.key).toBe('VISA');
    expect(cardsNetworkInstruments?.[1]?.key).toBe('RUPAY');
  });

  test('getSrDatPointsFromResponse should return with correct sr datapoints if response provided', () => {
    const props = {
      srResponse: sr_mock_response,
      method: 'card',
      instrument: 'VISA',
    };

    const srData = getSrDatPointsFromResponse(props);
    expect(srData.successful).toBe(1002);
    expect(srData.methodName).toBe('Cards');
  });

  test('getSrDatPointsFromResponse should return isError as true if endpoint fails', () => {
    const props = {
      srResponse: failed_sr_mock_response,
      method: 'card',
      instrument: 'VISA',
    };

    const srData = getSrDatPointsFromResponse(props);
    expect(srData.isError).toBe(true);
  });

  test('extractInstrumentType should return the correct instrument type', () => {
    const instrumentObj = {
      method: 'card',
      srKey: 'card.network.VISA',
      network: 'VISA',
    };

    const instrumentType = extractInstrumentType(Object.keys(instrumentObj));
    expect(instrumentType).toBe('network');

    const instrumentTypeWithEmpty = extractInstrumentType(Object.keys({}));
    expect(instrumentTypeWithEmpty).toBeNull();

    const instrumentObjUnsupported = {
      method: 'card',
      srKey: 'card.network.VISA',
      unsupportedType: 'unsupported',
    };
    const instrumentTypeUnsupported = extractInstrumentType(Object.keys(instrumentObjUnsupported));
    expect(instrumentTypeUnsupported).toBeNull();
  });
});
