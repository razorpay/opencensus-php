import track from 'merchant/views/PaymentLinks/PaymentLinks/Create/track';
import * as analytics from 'common/utils/analytics';

const screen = 'Create Payment Link';

describe('Create V1 Track Unit Test Case', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
  });

  test('should track CreateV1 fields', () => {
    // info const fields = ['sms_notify', 'email_notify', 'partial_payment', 'reminder_enable'];
    // if fields are exisiting in the array then 'actionName' should be 'changed'.

    track.segment.fields('sms_notify', 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Sms Notify',
      actionName: 'changed',
      screen,
      properties: { duplicate: 1 },
    });

    // if fields value are not exisiting in the fields array then 'actionName' should be 'added'
    track.segment.fields('sms_notify_v2', 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Sms Notify V2',
      actionName: 'added',
      screen,
      properties: { duplicate: 1 },
    });
  });

  test('should track CreateV1 form fields event', () => {
    track.segment.form.close('cancel');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link',
      actionName: 'closed',
      screen,
      properties: { actionName: 'cancel' },
    });
    const resp = {
      data: {
        id: 'PLINK23456',
        description: 'This is V1 version',
        customer_details: {
          customer_email: 'bhaskar.mishra@razorpay.com',
          customer_contact: '9205161382',
        },
      },
    };
    track.segment.form.success(resp, 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link',
      actionName: 'issued',
      screen,
      properties: {
        customerDetailsFilled: true,
        emailFilled: true,
        phoneNumberFilled: true,
        paymentLinksNotes: undefined,
        paymentLinkId: 'PLINK23456',
        paymentAmount: undefined,
        currency: undefined,
        paymentDescriptionFilled: true,
        partialsPayments: undefined,
        refrenceId: undefined,
        expiryDate: undefined,
        duplicateLink: 1,
        status: 'Success',
      },
    });
    track.segment.form.fail(['Payment Link API failed'], 0);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link',
      actionName: 'issued',
      screen,
      properties: {
        duplicateLink: 0,
        status: 'Failure',
        failureReason: 'Payment Link API failed',
      },
    });
  });

  test('should track paymentLinkCreate v1 event', () => {
    track.segment.paymentLinkCreate();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'create paymentlink',
      actionName: 'click',
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track paymentLinkIssue v1 event', () => {
    track.segment.paymentLinkIssue(1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'create payment link again',
      actionName: 'click',
      screen,
      properties: { origin: 'dashboard', clone: 1 },
    });
  });

  test('should track paymentLinkCancel event', () => {
    track.segment.paymentLinkCancel(1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link cancel',
      actionName: 'click',
      screen,
      properties: { origin: 'dashboard', clone: 1 },
    });
  });

  test('should track paymentLinkFail event', () => {
    const res = {
      data: {
        error: ['PL creation API timedout'],
      },
    };
    track.segment.paymentLinkFail(1, res);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link fail',
      actionName: 'click',
      screen,
      properties: { origin: 'dashboard', clone: 1, response: res },
    });
  });

  test('should track successToast event', () => {
    track.segment.successToast(1, 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link success toast close',
      actionName: 'click',
      screen,
      properties: { origin: 'dashboard', clone: 1, close: 1 },
    });
  });

  test('should track cloneStart event', () => {
    track.segment.cloneStart();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link clone start',
      actionName: 'click',
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track cloneClose event', () => {
    track.segment.cloneClose();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link clone close',
      actionName: 'click',
      screen,
      properties: { origin: 'dashboard' },
    });
  });

  test('should track cloneClose event', () => {
    track.segment.cloneComplete();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link clone complete',
      actionName: 'click',
      screen,
      properties: { origin: 'dashboard' },
    });
  });
});
