import track from 'merchant/views/PaymentButton/PaymentButton/Create/track';
import * as analytics from 'common/utils/analytics';

const paymentButtonId = 'Pb-123';

describe('Payment Button Create Track - UT', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    window.rzpQ = {
      paymentButtons: () => ({
        interaction: jest.fn(),
      }),
    };
    const lumberjackTrackMock = jest.fn();
    track.init(lumberjackTrackMock);
  });

  test('should track payment button create createOrEditSuccess event', () => {
    track.createOrEditSuccess(paymentButtonId);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'create button',
      actionName: 'success',
      screen: 'button create',
      toCleverTap: true,
      properties: {},
    });
  });

  test('should track payment button create createOrEditSuccess event', () => {
    track.createOrEditSuccess(paymentButtonId);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'create button',
      actionName: 'success',
      screen: 'button create',
      toCleverTap: true,
      properties: {},
    });
  });
  test('should track payment button create onClickButtonSettings event', () => {
    track.onClickButtonSettings();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'button settings',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create templateHover event', () => {
    track.templateHover();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'template',
      actionName: 'hover',
      screen: 'button create',
      toCleverTap: false,
      properties: { template: undefined },
    });
  });

  test('should track payment button create buttonTypeChange event', () => {
    track.buttonTypeChange();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'button type change',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create buttonTypeDiscardYes event', () => {
    track.buttonTypeDiscardYes();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'button type discard yes',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create buttonTypeDiscardNo event', () => {
    track.buttonTypeDiscardNo();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'button type discard no',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create buttonLabel event', () => {
    const event = {
      target: {
        value: 'hover',
      },
    };
    track.buttonLabel(event);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'text in button',
      actionName: 'input',
      screen: 'button create',
      toCleverTap: false,
      properties: { value: 'hover' },
    });
  });

  test('should track payment button create buttonTitle event', () => {
    track.buttonTitle();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'title',
      actionName: 'input',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create buttonAmount event', () => {
    track.buttonAmount();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount',
      actionName: 'input',
      screen: 'button create',
      toCleverTap: false,
      properties: { options: undefined },
    });
  });

  test('should track payment button create buttonTheme event', () => {
    track.buttonTheme();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'theme',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: { theme: undefined },
    });
  });

  test('should track payment button create buttonScreenNextSuccess event', () => {
    track.buttonScreenNextSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'next button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create onClickAmountField event', () => {
    track.onClickAmountField();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount add',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create changeCurrency event', () => {
    track.changeCurrency();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount change currency',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create amountScreenOpenMoreOptions event', () => {
    track.amountScreenOpenMoreOptions();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount more options',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create toggleMakeMandatory event', () => {
    track.toggleMakeMandatory(true);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount optional',
      actionName: 'toggle',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create amountFormDeleteField event', () => {
    track.amountFormDeleteField();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount delete field',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create openAdvanceOptions event', () => {
    track.openAdvanceOptions();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount advanced options',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create amountFieldCancel event', () => {
    track.amountFieldCancel();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount cancel',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create buttonTitle event', () => {
    track.buttonTitle();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'title',
      actionName: 'input',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create amountFieldDescription event', () => {
    track.amountFieldDescription(true);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount description',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: { status: 'Added' },
    });
  });

  test('should track payment button create amountFieldSaveSuccess event', () => {
    track.amountFieldSaveSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount save',
      actionName: 'success',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create amountScreenNextSuccess event', () => {
    track.amountScreenNextSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount next button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create amountScreenBackSuccess event', () => {
    track.amountScreenBackSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'amount back button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create donationScreenNextSuccess event', () => {
    track.donationScreenNextSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'donation next button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create donationScreenBackSuccess event', () => {
    track.donationScreenBackSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'donation back button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create customerScreenInputField event', () => {
    track.customerScreenInputField();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input add',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create customerScreenFieldType event', () => {
    const options = {
      label: 'Email',
    };
    track.customerScreenFieldType(options);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input choose type',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: { label: 'Email' },
    });
  });

  test('should track payment button create customerScreenInputFieldMoreOptions event', () => {
    track.customerScreenInputFieldMoreOptions();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input more options',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create customerScreenToggleMakeOptional event', () => {
    track.customerScreenToggleMakeOptional();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input optional',
      actionName: 'toggle',
      screen: 'button create',
      toCleverTap: false,
      properties: { isOptional: undefined },
    });
  });

  test('should track payment button create customerScreenDescriptionField event', () => {
    track.customerScreenDescriptionField();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input description',
      actionName: 'input',
      screen: 'button create',
      toCleverTap: false,
      properties: { status: 'Removed' },
    });
  });

  test('should track payment button create customerScreenDeleteField event', () => {
    track.customerScreenDeleteField();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input delete field',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create customerScreenCancelFieldChanges event', () => {
    track.customerScreenCancelFieldChanges();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input cancel',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create customerScreenFieldSaveSuccess event', () => {
    track.customerScreenFieldSaveSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input save',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create customerScreenNextSuccess event', () => {
    track.customerScreenNextSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input next button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create reviewScreenBackSuccess event', () => {
    track.customerScreenBackSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'input back button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create reviewScreenBackSuccess event', () => {
    track.reviewScreenBackSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'review back button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create onClickProgressBar event', () => {
    track.onClickProgressBar();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'button progress button',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: {},
    });
  });

  test('should track payment button create onClickProgressStep event', () => {
    track.onClickProgressStep();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'progress button step',
      actionName: 'click',
      screen: 'button create',
      toCleverTap: false,
      properties: { type: undefined },
    });
  });
});
