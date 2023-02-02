import { getCookie } from 'common/utils/cookies';
import { titleCase } from 'common/utils/rzp-utils';
import { getDeviceSource } from 'common/utils/analytics';
import { isMobileDevice } from 'merchant/components/Home/data';
import errorService from '@razorpay/universe-utils/errorService';
import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';

const throwAnalyticsException = (errorMessage) => {
  const error = new Error(errorMessage);

  errorService.captureError(error, {
    tags: {
      team: Teams.PARTNERSHIP,
    },
    rank: Ranks.P2,
  });
};

export const trackWithSegment = (props) => {
  const { objectName, actionName, screen, properties = {}, sendToCleverTap = true } = props;
  const eventName = titleCase(`${objectName} ${actionName}`);

  if (!objectName) {
    throwAnalyticsException('[analytics]: objectName cannot be empty');
  }

  if (!actionName) {
    throwAnalyticsException('[analytics]: actionName cannot be empty');
  }

  if (!screen) {
    throwAnalyticsException('[analytics]: screen cannot be empty');
  }

  if (/_/g.test(objectName)) {
    throwAnalyticsException(`[analytics]: expected objectName: ${objectName} to not have '_'`);

    return; // Don't capture the event if the objectName contains a "_".
  }

  if (/_/g.test(actionName)) {
    const errorMessage = `[analytics]: expected actionName: ${actionName} to not have '_'`;
    throwAnalyticsException(errorMessage);

    return; // Don't capture the event if the actionName contains a "_".
  }

  if (window.analytics && window.analytics.track) {
    window.analytics.track(
      eventName,
      {
        ...properties,
        screen: screen || 'Partner Signup Screen',
        eventTimestamp: new Date().toISOString(),
        experiment_ID: getCookie('auth_source') === 'website' ? 'Signup_experiment_1' : 'none',
        device_type: isMobileDevice(1020) ? 'mweb' : 'dweb', // We use 1020px, as we mark tablets and mobile as mweb (in analytics)
        source: getDeviceSource(),
        userId: 'UNKNWON_USER',
        partnerSignupFlag: true,
        pageUrl: window.location.pathname,
      },
      {
        integrations: {
          CleverTap: sendToCleverTap,
        },
      },
    );
  }
};
