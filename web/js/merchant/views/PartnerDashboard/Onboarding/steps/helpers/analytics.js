import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export const getOnContactSupportClicked = (screenName, handleOtherCTAClicks) => () => {
  handleOtherCTAClicks('Contact Support');

  analyticsTrack({
    objectName: 'Partner Contact Support',
    actionName: 'clicked',
    screen: screenName,
    properties: {
      location: 'partner onboarding base screen',
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
    toCleverTap: true,
  });
};

export const getOnTnCClicked = (screenName, handleOtherCTAClicks) => () => {
  handleOtherCTAClicks('Terms of use');

  analyticsTrack({
    objectName: 'Partner TnC',
    actionName: 'clicked',
    screen: screenName,
    properties: {
      location: 'partner onboarding base screen',
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
    toCleverTap: true,
  });
};

export const getOnPrivacyPolicyClicked = (screenName, handleOtherCTAClicks) => () => {
  handleOtherCTAClicks('Privacy Policy');

  analyticsTrack({
    objectName: 'Partner Privacy Policy',
    actionName: 'clicked',
    screen: screenName,
    properties: {
      location: 'partner onboarding base screen',
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
    toCleverTap: true,
  });
};
