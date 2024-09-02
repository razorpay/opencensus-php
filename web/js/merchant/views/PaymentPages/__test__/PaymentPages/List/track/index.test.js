import track from 'merchant/views/PaymentPages/PaymentPages/List/track';
import * as analytics from 'common/utils/analytics';

describe('List Analytics', () => {
  const lumberjackTrackMock = jest.fn();
  window.rzpQ = {
    paymentPages: () => ({
      interaction: jest.fn(),
    }),
  };
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    track.init(lumberjackTrackMock);
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should call sendToLumberjack and sendToSegment with correct arguments on load', () => {
    track.init(lumberjackTrackMock);
    track.load();
    const properties = {
      section: 'list payment page',
    };
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'intial',
      actionName: 'load',
      screen: 'list payment page',
      toCleverTap: false,
      properties,
    });
  });

  test('should call sendToLumberjack and sendToSegment with correct arguments on load', () => {
    const event = {
      target: {
        value: 'click',
      },
    };
    track.searchCount(event);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'search with count',
      actionName: 'input',
      screen: 'list payment page',
      toCleverTap: false,
      properties: { value: 'click' },
    });
  });

  test('should call searchStatus', () => {
    const event = {
      target: {
        value: 'click',
      },
    };
    track.searchStatus(event);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'search with status',
      actionName: 'click',
      screen: 'list payment page',
      toCleverTap: false,
      properties: { value: 'click' },
    });
  });

  test('should call searchTitle', () => {
    const event = {
      target: {
        value: 'click',
      },
    };
    track.searchTitle(event);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'search with title',
      actionName: 'input',
      screen: 'list payment page',
      toCleverTap: false,
      properties: { value: 'click' },
    });
  });

  test('should call search', () => {
    const params = {
      number: '9205161161',
    };
    track.search(params);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'search ',
      actionName: 'click',
      screen: 'list payment page',
      toCleverTap: false,
      properties: { number: '9205161161' },
    });
  });

  test('should call searchClear', () => {
    track.searchClear();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'clear search filters',
      actionName: 'click',
      screen: 'list payment page',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should call createPaymentPage', () => {
    track.createPaymentPage();
    const properties = {
      section: 'list payment page',
    };
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'create page',
      actionName: 'clicked',
      screen: 'Select page of your choice',
      toCleverTap: false,
      properties,
    });
  });

  test('should call paginate', () => {
    const data = {
      number: '123',
    };
    track.paginate('next', data);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'browse next',
      actionName: 'click',
      screen: 'list payment page',
      toCleverTap: false,
      properties: data,
    });
  });

  test('should call takeTour', () => {
    track.takeTour();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'take tour',
      actionName: 'clicked',
      screen: 'list payment page',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should call viewDoc', () => {
    track.viewDoc();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'view documentation',
      actionName: 'clicked',
      screen: 'list payment page',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should call copyUrl', () => {
    track.copyUrl();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'copy url',
      actionName: 'clicked',
      screen: 'list payment page',
      toCleverTap: false,
      properties: {},
    });
  });
});
