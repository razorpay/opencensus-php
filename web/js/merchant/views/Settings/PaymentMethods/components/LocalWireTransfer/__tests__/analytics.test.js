import {
  trackAccountCopied,
  trackTandCPopupOpened,
  trackActivateClick,
  trackAccountActivated,
  trackAccountError,
  trackCheckBalanceClicked,
  trackCheckBalanceFailed,
  trackSubmitPayoutRequest,
  trackSubmitPayoutSuccess,
  trackSubmitPayoutFailed,
  trackWithdrawClicked,
} from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/analytics';

import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import * as analytics from 'common/utils/analytics';

jest.mock('common/utils/analytics');

describe('Tests for analytics events', () => {
  test('trackAccountCopied should call the correct analytic events', () => {
    const currency = 'usd';
    trackAccountCopied(currency);
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts Copied',
      actionName: 'clicked',
      properties: {
        accountCoppied: true,
        accountName: currency,
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackTandCPopupOpened should call the correct analytic events', () => {
    trackTandCPopupOpened();
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts list',
      actionName: 'clicked',
      properties: {
        isActivateClicked: true,
        isPopupOpened: true,
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackActivateClick should call the correct analytic events', () => {
    trackActivateClick('usd');
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts usd activation popup',
      actionName: 'clicked',
      properties: {
        isTermsAndConditionsAccepted: true,
        activateClicked: true,
        isActivated: false,
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackAccountActivated should call the correct analytic events', () => {
    trackAccountActivated('usd');
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts usd activation',
      actionName: 'response',
      properties: {
        isActivated: true,
        status: 'success',
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackAccountError should call the correct analytic events', () => {
    const error = 'dummy error';
    trackAccountError(error, 'usd');
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts usd activation',
      actionName: 'response',
      properties: {
        errorReason: error,
        isActivated: false,
        status: 'error',
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackCheckBalanceClicked should call the correct analytic events', () => {
    trackCheckBalanceClicked();
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts check balance',
      actionName: 'click',
      properties: {
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackCheckBalanceFailed should call the correct analytic events', () => {
    trackCheckBalanceFailed();
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts check balance',
      actionName: 'response',
      properties: {
        status: 'failed',
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackSubmitPayoutRequest should call the correct analytic events', () => {
    trackSubmitPayoutRequest();
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts submit payout',
      actionName: 'request',
      properties: {
        status: 'init',
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackSubmitPayoutSuccess should call the correct analytic events', () => {
    trackSubmitPayoutSuccess();
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts submit payout',
      actionName: 'response',
      properties: {
        status: 'success',
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackSubmitPayoutFailed should call the correct analytic events', () => {
    trackSubmitPayoutFailed();
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts submit payout',
      actionName: 'response',
      properties: {
        status: 'failed',
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });

  test('trackWithdrawClicked should call the correct analytic events', () => {
    trackWithdrawClicked();
    expect(analytics.analyticsTrack).toBeCalledTimes(1);
    expect(analytics.analyticsTrack).toBeCalledWith({
      objectName: 'b2b accounts withdraw money',
      actionName: 'click',
      properties: {
        location: 'Payment Methods',
        ...getCommonSegmentProperties(),
      },
      screen: 'settings',
    });
  });
});
