import track from 'merchant/views/PaymentHandle/track';
import * as analytics from 'common/utils/analytics';

const properties = { source: 'Dashboard' };

describe('UT Test Cases for Track File', () => {
  test('should track paymentIdClick', () => {
    track.paymentIdClick();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme payment id',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });

  test('should track search', () => {
    track.search();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme search',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });

  test('should track searchPaymentId', () => {
    const prop = { target: { value: 'pl_invD23Fgh' } };
    track.searchPaymentId(prop);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme search with payment id',
      actionName: 'input',
      screen: 'razorpayme',
      toLumberjack: true,
      properties: { source: 'Dashboard', value: 'pl_invD23Fgh' },
    });
    track.searchStatus(prop);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme search with status',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties: { source: 'Dashboard', value: 'pl_invD23Fgh' },
    });
  });

  test('should track searchEmail', () => {
    const prop = { target: { value: 'bhaskar.mishra@razorpay.com' } };
    track.searchEmail(prop);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme search with email',
      actionName: 'input',
      screen: 'razorpayme',
      toLumberjack: true,
      properties: { source: 'Dashboard', value: 'bhaskar.mishra@razorpay.com' },
    });
  });

  test('should track searchCount', () => {
    const prop = { target: { value: 'bhaskar.mishra@razorpay.com' } };
    track.searchCount(prop);
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme search with count',
      actionName: 'input',
      screen: 'razorpayme',
      toLumberjack: true,
      properties: { source: 'Dashboard', value: 'bhaskar.mishra@razorpay.com' },
    });
  });

  test('should track onboarding start success', () => {
    track.onboarding.startSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme onboarding start success',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });

  test('should track onboarding editLinkSuccess', () => {
    track.onboarding.editLinkSuccess();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme edit link success',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });

  test('should track onboarding editLinkSave', () => {
    track.onboarding.editLinkSave();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme edit link save',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });

  test('should track onboarding editLinkCancel', () => {
    track.onboarding.editLinkCancel();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme edit link cancel',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });

  test('should track onboarding start success', () => {
    track.onboarding.getStarted();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme get started success',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });

  test('should track detail copyLink', () => {
    track.detail.copyLink();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme copy link',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });

  test('should track detail shareLink', () => {
    track.detail.shareLink();
    expect(analytics.analyticsTrack).toHaveBeenCalledWith({
      objectName: 'razorpayme share link initiate',
      actionName: 'click',
      screen: 'razorpayme',
      toLumberjack: true,
      properties,
    });
  });
});
