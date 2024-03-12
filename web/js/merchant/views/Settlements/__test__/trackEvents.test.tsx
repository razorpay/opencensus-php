import { analyticsTrack } from 'common/utils/analytics';
import {
  trackEnableNow,
  trackConfirmEnableNow,
  trackEnableNowClose,
  trackSettleNowClicked,
  trackSettleNowInfoHover,
  trackSettleAmountUpdated,
  trackSettleNowShowBreakup,
  trackSettleNowCloseClick,
  trackSettleNowCloseReason,
  trackSettleNowConfirmClose,
  trackSettleNowFirstConfirm,
  trackSettleNowSecondConfirm,
  trackSettleNowCancelConfirm,
} from 'merchant/views/Settlements/trackEvents';

test('should trigger correct analytics event for trackSettleNow', () => {
  let numOfCalls = 0;

  trackSettleNowClicked('Home');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Settle Now',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Settlement Initiated',
    },
    screen: 'Home Dashboard',
  });

  trackSettleNowClicked('Settlements');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Settle Now',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Settlement Initiated',
    },
    screen: 'Settlements',
  });

  trackSettleNowClicked('Instant Settlements');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Settle Now',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Settlement Initiated',
    },
    screen: 'Ondemand Settlements Tab',
  });

  trackSettleNowClicked('Empty State');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Settle Now Empty State',
    objectName: 'Settle Now',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Settlement Initiated',
      location: 'Empty State',
    },
    screen: 'Ondemand Settlements Tab',
  });
});

test('should trigger correct analytics event for trackEnableNow', () => {
  let numOfCalls = 0;

  trackEnableNow('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Scheduled ES Enable Now',
    properties: {
      behaviour: undefined,
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      location: 'Banner inside',
    },
    screen: 'Ondemand Settlements Tab',
  });

  trackEnableNow('/settlements');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Scheduled ES Enable Now',
    properties: {
      behaviour: undefined,
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      location: 'Tab Banner',
    },
    screen: 'Settlements',
  });

  trackEnableNow('/instantsettlements');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Scheduled ES Enable Now',
    properties: {
      behaviour: undefined,
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      location: 'Tab Banner',
    },
    screen: 'Ondemand Settlements Tab',
  });
});

test('should trigger correct analytics event for trackConfirmEnableNow', () => {
  let numOfCalls = 0;

  trackConfirmEnableNow('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Scheduled ES Enable Modal Confirm',
    properties: {
      behaviour: 'Confirm Enable ES',
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      location: 'Banner inside',
    },
    screen: 'Ondemand Settlements Tab',
  });

  trackEnableNowClose('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Scheduled ES Enable Modal Close',
    properties: {
      behaviour: 'Cancel Enable ES modal',
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      location: 'Banner inside',
    },
    screen: 'Ondemand Settlements Tab',
  });

  trackSettleNowClicked('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Settle Now',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Settlement Initiated',
    },
  });

  trackSettleNowInfoHover('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Hovered',
    objectName: 'ES Restricted Info icon',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Seen tooltip',
    },
  });

  trackSettleAmountUpdated('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Updated',
    objectName: 'Settlement Amount',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Update Prefilled Amount',
    },
  });

  trackSettleNowShowBreakup('banner', true);
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Show breakup',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'After Confirm',
    },
  });

  trackSettleNowCloseClick('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Settle Now Modal Close',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Before Confirm',
    },
  });

  trackSettleNowShowBreakup('banner', false);
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Show breakup',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Before Confirm',
    },
  });

  trackSettleNowCloseReason('banner', 'test');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Selected',
    objectName: 'Reason for Close',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Cancel Reason | test',
    },
  });

  trackSettleNowConfirmClose('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Settle Now Confirm Close',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Exits Settlement | Churn',
    },
  });

  trackSettleNowFirstConfirm('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Settle Now First Confirm',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'First Confirm',
    },
  });

  trackSettleNowSecondConfirm('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: 'Settle Now Second Confirm',
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Second Confirm',
    },
  });

  trackSettleNowCancelConfirm('banner');
  expect(analyticsTrack).toBeCalledTimes(++numOfCalls);
  expect(analyticsTrack).toBeCalledWith({
    actionName: 'Clicked',
    objectName: "Settle Now No Don't",
    properties: {
      es_automatic: false,
      es_on_demand: false,
      es_on_demand_restricted: false,
      stage: 'Cancel | Back to Settlement Initiated',
    },
  });
});
