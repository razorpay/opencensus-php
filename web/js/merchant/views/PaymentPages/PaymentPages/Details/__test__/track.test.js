import _track from 'merchant/views/PaymentPages/PaymentPages/Details/track';
import { titleCase } from 'common/utils/rzp-utils';
import * as analytics from 'common/utils/analytics';
import { extraProperties } from 'merchant/views/PaymentPages/PaymentPages/Details/__test__/mock/storefrontData';

const actionName = 'click';
const screen = 'details payment page';

describe('_track', () => {
  let sendToLumberjackMock;

  beforeAll(() => {
    sendToLumberjackMock = jest.fn();
    _track.init(sendToLumberjackMock);

    window.rzpQ = {
      component: jest.fn(),
      paymentPages: () => ({
        success: jest.fn(),
        interaction: jest.fn(),
      }),
    };
    window.rzp_user = {
      /* mocked user object */
    };
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should call sendToLumberjack and sendToSegment when pageOpen is called', () => {
    _track.pageOpen();

    expect(sendToLumberjackMock).toHaveBeenCalled();
    // expect(sendToLumberjackMock).toHaveBeenCalledWith('page_open');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ actionName: 'open', objectName: 'page', screen }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when pageStatus is called with a type', () => {
    const type = 'status_type';
    _track.pageStatus(type);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith(`status.${type}`);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: `page status ${type}`, actionName, screen }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when changeExpiry is called', () => {
    _track.changeExpiry();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('expiry_date_change');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'change expiry', actionName, screen }),
    );
  });

  test('should call sendToLumberjack and sendToSegment with value when noExpiry is called', () => {
    const value = true;
    _track.noExpiry(value);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('no_expiry_checkbox_click', { value });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'no expire',
        actionName,
        properties: expect.objectContaining({ value }),
      }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when updateDate is called', () => {
    _track.updateDate();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('expiry_date_select');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'update date', actionName }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when cancelExpiry is called', () => {
    _track.cancelExpiry();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('expiry_cancel');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'cancel expiry', actionName }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when saveExpiry is called', () => {
    _track.saveExpiry();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('expiry_date_save');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'save expiry', actionName }),
    );
  });

  test('should call sendToLumberjack, sendToSegment, and selfServeTrackInitiate when duplicatePage is called', () => {
    const properties = {
      merchantId: 'Unknown',
      mode: null,
      userId: 'Unknown',
      userRole: 'Unknown',
      section: 'Details Payment Page',
      browser: undefined,
      device_type: 'dweb',
    };

    _track.duplicatePage(extraProperties);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('duplicate_page');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'duplicate page',
      actionName,
      screen: 'Create storefront page',
      toCleverTap: false,
      properties: {
        ...properties,
        ...extraProperties,
      },
    });
    // expect(selfServeTrackInitiateMock).toHaveBeenCalled();
  });

  test('should call sendToLumberjack, sendToSegment, and selfServeTrackInitiate when editPage is called', () => {
    const properties = {
      section: 'Details Payment Page',
      merchantId: 'Unknown',
      mode: null,
      userId: 'Unknown',
      userRole: 'Unknown',
      browser: undefined,
      device_type: 'dweb',
    };
    _track.editPage(extraProperties);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('edit_page');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'edit page',
      actionName,
      screen: 'Edit storefront page',
      toCleverTap: false,
      properties: {
        ...properties,
        ...extraProperties,
      },
    });
    // expect(selfServeTrackInitiateMock).toHaveBeenCalled();
  });

  test('should call sendToLumberjack and sendToSegment when addNewNote is called', () => {
    _track.addNewNote();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('add_new_note');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'add new note', actionName: 'clicked' }),
    );
  });

  test('should call sendToLumberjack and sendToSegment with modified when saveNotes is called', () => {
    const modified = true;
    _track.saveNotes(modified);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('notes', { modified });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'save notes',
        actionName: 'clicked',
        properties: expect.objectContaining({ modified }),
      }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when confirmDeleteNotes is called', () => {
    _track.confirmDeleteNotes();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('notes_closed');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'confirm delete notes', actionName: 'clicked' }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when cancelDeleteNotes is called', () => {
    _track.cancelDeleteNotes();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('notes.close');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'cancel delete notes', actionName: 'clicked' }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when copyUrl is called', () => {
    _track.copyUrl();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('copy_url');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'copy url', actionName: 'clicked' }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when share is called', () => {
    _track.share();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('share');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'share', actionName: 'clicked' }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when showMore is called', () => {
    _track.showMore();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('show_more');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'show more', actionName: 'clicked' }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when updateStock is called', () => {
    const properties = {
      section: 'Details Payment Page',
      merchantId: 'Unknown',
      mode: null,
      userId: 'Unknown',
      userRole: 'Unknown',
      browser: undefined,
      device_type: 'dweb',
    };
    _track.updateStock(extraProperties);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('edit_page');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Update Stock',
      actionName,
      screen: 'Details Payment Page',
      toCleverTap: false,
      properties: {
        ...properties,
        ...extraProperties,
      },
    });
  });

  test('should call sendToLumberjack and sendToSegment with extension when downloadReport is called', () => {
    const extension = 'csv';
    _track.downloadReport(extension);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('download_report', { extension });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'download report',
        actionName: 'clicked',
        properties: expect.objectContaining({ extension }),
      }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when settingsDropdown is called', () => {
    const properties = {
      section: 'Details Payment Page',
      merchantId: 'Unknown',
      mode: null,
      userId: 'Unknown',
      userRole: 'Unknown',
      browser: undefined,
      device_type: 'dweb',
    };
    _track.settingsDropdown(extraProperties);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('settings_dropdown');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Settings Dropdown',
      actionName,
      screen: 'Details Payment Page',
      toCleverTap: false,
      properties: {
        ...properties,
        ...extraProperties,
      },
    });
  });

  test('should call sendToLumberjack, sendToSegment, and selfServeTrackInitiate when receiptSettings is called', () => {
    _track.receiptSettings();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('receipt_settings');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'receipt settings', actionName: 'clicked' }),
    );
    // expect(selfServeTrackInitiateMock).toHaveBeenCalled();
  });

  test('should call sendToLumberjack, sendToSegment, and selfServeTrackInitiate when pageSettings is called', () => {
    _track.pageSettings();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('page_settings');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'page settings', actionName: 'clicked' }),
    );
    // expect(selfServeTrackInitiateMock).toHaveBeenCalled();
  });

  test('should call sendToLumberjack and sendToSegment with value when searchPaymentId is called', () => {
    const value = 'payment_id';
    const event = { target: { value } };
    _track.searchPaymentId(event);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('payment_id_enter', { value });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'search with payment id',
        actionName: 'input',
        properties: expect.objectContaining({ value }),
      }),
    );
  });

  test('should call sendToLumberjack and sendToSegment with value when searchStatus is called', () => {
    const value = 'status_value';
    const event = { target: { value } };
    _track.searchStatus(event);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('status_click', { value });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'search with status',
        actionName: 'click',
        properties: expect.objectContaining({ value }),
      }),
    );
  });

  test('should call sendToLumberjack and sendToSegment with value when searchEmail is called', () => {
    const value = 'email';
    const event = { target: { value } };
    _track.searchEmail(event);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('email_id_enter', { value });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'search with email',
        actionName: 'input',
        properties: expect.objectContaining({ value }),
      }),
    );
  });

  test('should call sendToLumberjack and sendToSegment with value when searchCount is called', () => {
    const value = 10;
    const event = { target: { value } };
    _track.searchCount(event);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('count_enter', { value });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'search with count',
        actionName: 'input',
        properties: expect.objectContaining({ value }),
      }),
    );
  });

  test('should call sendToLumberjack and sendToSegment with modified when saveNotes is called', () => {
    const modified = true;
    _track.saveNotes(modified);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('notes', { modified });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'save notes', actionName: 'clicked' }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when confirmDeleteNotes is called', () => {
    _track.confirmDeleteNotes();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('notes_closed');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'confirm delete notes', actionName: 'clicked' }),
    );
  });

  test('should call sendToLumberjack and sendToSegment with params when search is called', () => {
    const params = {
      /* mocked search parameters */
    };
    _track.search(params);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('search', { params });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: 'search',
        actionName,
        properties: expect.objectContaining({ params }),
      }),
    );
  });

  test('should call sendToLumberjack and sendToSegment when paymentIdClick is called', () => {
    _track.paymentIdClick();

    // expect(sendToLumberjackMock).toHaveBeenCalledWith('payment_id_click');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'payment id', actionName }),
    );
  });

  test('should call sendToLumberjack and sendToSegment with eventName and data when shareModalEvents is called', () => {
    const eventName = 'event_name';
    const data = {
      value: 'test',
    };
    _track.shareModalEvents(eventName, data);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith(`share_${eventName}`, data);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: `share ${titleCase(eventName)}`,
        actionName,
        properties: expect.objectContaining(data),
      }),
    );
  });

  test('should call sendToLumberjack and analyticsTrack with specific values when pageStatus is called with a type', () => {
    const type = 'status_type';
    _track.pageStatus(type);

    // expect(sendToLumberjackMock).toHaveBeenCalledWith(`status.${type}`);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith(
      expect.objectContaining({
        objectName: `page status ${type}`,
        actionName,
        screen,
      }),
    );
  });
});
