import { analyticsTrack } from 'common/utils/analytics';

export const platformFeeOpenedAnalytics = (id: string | undefined): void => {
  analyticsTrack({
    screen: 'platform fee page',
    objectName: 'route partnership platform fee',
    actionName: 'tab opened',
    properties: {
      mid: id,
    },
    toLumberjack: true,
  });
};

export const platformFeeDetailsOpenedAnalytics = (id: string | undefined): void => {
  analyticsTrack({
    screen: 'platform fee details page',
    objectName: 'route partnership platform fee details',
    actionName: 'tab opened',
    properties: {
      mid: id,
    },
    toLumberjack: true,
  });
};

export const paymentDetailsOpenedAnalytics = (id: string | undefined): void => {
  analyticsTrack({
    screen: 'payment details page',
    objectName: 'route partnership payment details',
    actionName: 'tab opened for platform fee',
    properties: {
      mid: id,
    },
    toLumberjack: true,
  });
};

export const platformFeeTabDisplayedAnalytics = (id: string | undefined): void => {
  analyticsTrack({
    screen: 'route payment page',
    objectName: 'route partnership platform fee',
    actionName: 'tab displayed',
    properties: {
      mid: id,
    },
    toLumberjack: true,
  });
};

export const linkedAccountTabOpenedAnalytics = (id: string | undefined): void => {
  analyticsTrack({
    screen: 'Linked account page',
    objectName: 'linked account',
    actionName: 'tab opened',
    properties: {
      mid: id,
    },
    toLumberjack: true,
  });
};

export const linkedAccountDashboardAccessGrantedAnalytics = (id: string | undefined): void => {
  analyticsTrack({
    screen: 'Linked account page',
    objectName: 'linked account',
    actionName: 'dashboard access granted',
    properties: {
      mid: id,
    },
    toLumberjack: true,
  });
};
