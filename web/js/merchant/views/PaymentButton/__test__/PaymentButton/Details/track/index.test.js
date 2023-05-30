import track from 'merchant/views/PaymentButton/PaymentButton/Details/track';
import * as analytics from 'common/utils/analytics';

const buttonId = 'pb_123-3467';

describe('Payment Button List Track - UT', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    window.rzpQ = {
      paymentButtons: () => ({
        interaction: jest.fn(),
      }),
    };
    const lumberjackTrackMock = jest.fn();
    track.init(lumberjackTrackMock, buttonId);
  });

  test('should track payment button details detailsStart event', () => {
    track.detailsStart();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'details',
      actionName: 'open',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details optionsOpen event', () => {
    track.optionsOpen();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'options configurations',
      actionName: 'open',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details optionsOpenEdit event', () => {
    track.optionsOpenEdit();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'options edit',
      actionName: 'click',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details optionsOpenDuplicate event', () => {
    track.optionsOpenDuplicate();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'options duplicate',
      actionName: 'click',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details optionsOpenSettings event', () => {
    track.optionsOpenSettings('sms');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'open settings',
      actionName: 'click',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { via: 'sms', buttonId },
    });
  });

  test('should track payment button details settingsCustomMessage event', () => {
    track.settingsCustomMessage('sms', 'get payment done');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'settings custom message',
      actionName: 'input',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { via: 'sms', message: 'get payment done', buttonId },
    });
  });

  test('should track payment button details settingsReceiptConfigure event', () => {
    track.settingsReceiptConfigure();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'receipts modal',
      actionName: 'open',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details settingsCancel event', () => {
    track.settingsCancel();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'settings modal cancel',
      actionName: 'click',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details settingsSaveFail event', () => {
    track.settingsSaveFail('id invalid');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'settings save',
      actionName: 'fail',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { error: 'id invalid', buttonId },
    });
  });

  test('should track payment button details settingsSave event', () => {
    track.settingsSave();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'settings save',
      actionName: 'success',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details openGetCodeModal event', () => {
    track.openGetCodeModal();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'get code modal',
      actionName: 'open',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details copyCode event', () => {
    track.copyCode();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'get code copy code',
      actionName: 'click',
      screen: 'details payment button',
      toCleverTap: true,
      properties: { buttonId },
    });
  });

  test('should track payment button details closeGetCodeModal event', () => {
    track.closeGetCodeModal();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'get code modal',
      actionName: 'close',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details seeDocumentation event', () => {
    track.seeDocumentation();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'get code documentation',
      actionName: 'click',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details testButton event', () => {
    track.testButton();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'success test button',
      actionName: 'click',
      screen: 'details payment button',
      toCleverTap: true,
      properties: { buttonId },
    });
  });

  test('should track payment button details paymentReceiptsOpen event', () => {
    track.paymentReceiptsOpen();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'receipts modal',
      actionName: 'open',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { buttonId },
    });
  });

  test('should track payment button details receiptsType event', () => {
    track.receiptsType('sms', 'UPI');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'receipts type UPI',
      actionName: 'select',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { via: 'sms', buttonId },
    });
  });

  test('should track payment button details inputFieldCheckbox event', () => {
    track.inputFieldCheckbox('sms', true);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'receipts customer info',
      actionName: 'checked',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { via: 'sms', buttonId },
    });
  });

  test('should track payment button details details80gCheckbox event', () => {
    track.details80gCheckbox('sms', true);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'receipts 80G',
      actionName: 'checked',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { via: 'sms', buttonId },
    });
  });

  test('should track payment button details customMessageCheckbox event', () => {
    track.customMessageCheckbox('sms', true);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'settings custom message',
      actionName: 'checked',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { via: 'sms', buttonId },
    });
  });

  test('should track payment button details redirectURLCheckbox event', () => {
    track.redirectURLCheckbox('sms', true);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'settings redirect',
      actionName: 'checked',
      screen: 'details payment button',
      toCleverTap: false,
      properties: { via: 'sms', buttonId },
    });
  });

  test('should track payment button details pluginClick event', () => {
    track.pluginClick('shopify', true);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Shopify documentation link',
      actionName: 'click',
      screen: 'details payment button',
      toCleverTap: true,
      properties: { is_direct_plugin: true, buttonId },
    });
  });
});
