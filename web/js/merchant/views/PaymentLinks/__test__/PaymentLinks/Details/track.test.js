import track from 'merchant/views/PaymentLinks/PaymentLinks/Details/track/index';
import * as analytics from 'common/utils/analytics';

const PAYMENT_LINK_TYPES = ['standard', 'upi_pl'];

describe('UT Test Cases for Track File', () => {
  beforeAll(() => {
    jest.spyOn(analytics, 'analyticsTrack').mockImplementation(jest.fn);
    window.rzpQ = {
      component: jest.fn(),
      paymentLinks: () => ({
        interaction: jest.fn(),
      }),
    };
  });

  const lumberjackTrack = jest.fn();
  track.init(lumberjackTrack);

  test.each(PAYMENT_LINK_TYPES)('should check for resend Start Event', (paymentLinkType) => {
    track.resendStart(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      actionName: 'click',
      objectName: 'Payment Link Resend Start',
      properties: {
        origin: 'dashboard',
        type: paymentLinkType,
      },
      screen: 'Create Payment Link',
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for resend close Event', (paymentLinkType) => {
    track.resendClose(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      actionName: 'clicked',
      objectName: 'Payment Link Resend Close',
      properties: {
        origin: 'dashboard',
        type: paymentLinkType,
      },
      screen: 'Create Payment Link',
    });
  });

  test('should trigger resendIssue event', () => {
    track.resendIssue();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      actionName: 'click',
      objectName: 'Merchant Resend',
      properties: {
        origin: 'dashboard',
      },
      screen: 'Create Payment Link',
    });
  });

  test.each([1, 0])('should check for resend Success Event', (close) => {
    track.resendSuccess(close);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Resend Success Toast',
      actionName: 'click',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', close },
    });
  });

  test('should trigger onCopyClick event', () => {
    track.onCopyClick();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Create Copy',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: {
        origin: 'dashboard',
      },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for onDetailsView Event', (paymentLinkType) => {
    track.onDetailsView(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Details View',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for onDeactivate Event', (paymentLinkType) => {
    track.onDeactivate(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Deactivate',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for onDeactivateSuccess Event', (paymentLinkType) => {
    track.onDeactivateSuccess(paymentLinkType, 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Deactivate Success',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType, close: 1 },
    });
    track.onDeactivateSuccess(paymentLinkType, 0);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Deactivate Success',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType, close: 0 },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for onDeactivateConfirm Event', (paymentLinkType) => {
    track.onDeactivateConfirm(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Deactivate Confirm',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for onDeactivateAbort Event', (paymentLinkType) => {
    track.onDeactivateAbort(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Deactivate Abort',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for onUpdatePartial Event', (paymentLinkType) => {
    track.onUpdatePartial(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Partial',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test('should check for onUpdateReciept Event', () => {
    track.onUpdateReciept('receipt_confirm', 'standard', 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Receipt Confirm',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: 'standard', modified: 1 },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for updateReferenceId Event', (paymentLinkType) => {
    track.updateReferenceId(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Referenceid',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for abortReferenceId Event', (paymentLinkType) => {
    track.abortReferenceId(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Referenceid Cancel',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for saveReferenceId Event', (paymentLinkType) => {
    track.saveReferenceId(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Referenceid Success',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for updateReminderTick Event', (paymentLinkType) => {
    track.updateReminderTick(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Reminder Tick',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for updateExpiry Event', (paymentLinkType) => {
    track.updateExpiry(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Expiry Tick',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for saveUpdateExpiry Event', (paymentLinkType) => {
    track.saveUpdateExpiry(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Expiry Success',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for saveUpdateExpiry Event', (paymentLinkType) => {
    track.cancelUpdateExpiry(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Expiry Cancel',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for updateNotes Event', (paymentLinkType) => {
    track.updateNotes(paymentLinkType, 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Notes',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType, modified: 1 },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for updateNotesClosed Event', (paymentLinkType) => {
    track.updateNotesClosed(paymentLinkType, 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Notes Closed',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType, modified: 1 },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for onClone Event', (paymentLinkType) => {
    track.onClone(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Clone Start',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for onResend Event', (paymentLinkType) => {
    track.onResend(paymentLinkType);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Resend Start',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test.each(PAYMENT_LINK_TYPES)('should check for notifyLink Event', (paymentLinkType) => {
    track.notifyLink(paymentLinkType, 'notes');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Payment Link Update Resend Notes',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: paymentLinkType },
    });
  });

  test('should check for paymentUpdateDetail Event', () => {
    track.paymentUpdateDetail('update pl', 1);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Update Pl',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard' },
    });
  });

  test('should check for updateNotesClose Event', () => {
    track.updateNotesClose('close PL');
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Close Pl',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard' },
    });
  });

  test('should check for updateReminderEnable Event', () => {
    track.updateReminderEnable('update PL', { type: 'standard' });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Update Pl',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: 'standard' },
    });
  });

  test('should check for paymentLinkDetailsUpdateView Event', () => {
    track.paymentLinkDetailsUpdateView('update PL', { type: 'standard' });
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'Update Pl',
      actionName: 'clicked',
      screen: 'Create Payment Link',
      properties: { origin: 'dashboard', type: 'standard' },
    });
  });
});
