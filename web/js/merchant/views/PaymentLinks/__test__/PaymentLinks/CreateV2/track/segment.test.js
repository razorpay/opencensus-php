import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track/segment';
import * as analytics from 'common/utils/analytics';

const PL_TEMPLATE_TYPE = 'UPI';
const screen = 'Create Payment Link';
const properties = {
  templateType: PL_TEMPLATE_TYPE,
  userId: 'Unknown',
  mode: 'test',
  userRole: 'Unknown',
  merchantId: 'Unknown',
};

describe('Payment Link CreateV2 Segment Track - UT', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    window.rzp_user = {};
    window.rzpQ = {
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  test('should track payment link linkTypeSelection event', () => {
    track.linkTypeSelection.select('UPI');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'template',
      actionName: 'selected',
      screen,
      properties,
    });
  });

  test('should track fields paymentFor event', () => {
    track.fields.paymentFor();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'description',
      actionName: 'added',
      screen,
      properties,
    });
  });

  test('should track fields partialPayment event', () => {
    track.fields.partialPayment();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'partial payment',
      actionName: 'selected',
      screen,
      properties,
    });
  });

  test('should track fields notifySms event', () => {
    track.fields.notifySms();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'sms notify',
      actionName: 'changed',
      screen,
      properties,
    });
  });

  test('should track fields notifyEmail event', () => {
    track.fields.notifyEmail();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'email notify',
      actionName: 'changed',
      screen,
      properties,
    });
  });
  test('should track fields receipt event', () => {
    track.fields.receipt();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'receipt',
      actionName: 'added',
      screen,
      properties,
    });
  });
  test('should track fields expiryDate event', () => {
    track.fields.expiryDate();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'expiry date',
      actionName: 'selected',
      screen,
      properties,
    });
  });
  test('should track fields expiryTime event', () => {
    track.fields.expiryTime();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'expiry time',
      actionName: 'selected',
      screen,
      properties,
    });
  });
  test('should track fields reminders event', () => {
    track.fields.reminders();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'reminders',
      actionName: 'selected',
      screen,
      properties,
    });
  });

  test('should track form close event', () => {
    track.form.close();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link',
      actionName: 'closed',
      screen,
      properties,
    });
  });

  test('should track form cancelConfirm event', () => {
    const amount = 200;
    track.form.cancelConfirm({ amount });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link cancel',
      actionName: 'confirmed',
      screen,
      properties: {
        amount,
        ...properties,
      },
    });
  });

  test('should track form success event', () => {
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
    track.form.success(resp, 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link',
      actionName: 'issued',
      screen: 'Create Payment Link',
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
        ...properties,
      },
    });
  });

  test('should track form fail event', () => {
    const failureReason = { status: 400, reason: 'Partial Amount is deactivated for the user' };
    const duplicateLink = 1;
    track.form.fail(failureReason, duplicateLink);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'payment link',
      actionName: 'issued',
      screen,
      properties: {
        duplicateLink,
        status: 'Failure',
        failureReason,
        ...properties,
      },
    });
  });
});
