import errorService from '@razorpay/universe-cli/errorService';
import { DASHBOARD_PRIORITY_RANKS, DASHBOARD_TEAMS } from '@libs/shared-types';

interface WebViewEventProps {
  eventType: string;
  data: any;
  errorTeam?: DASHBOARD_TEAMS;
  errorRank?: DASHBOARD_PRIORITY_RANKS;
}

/**
 * Dispatches an event to the React Native WebView, and handles errors by logging them using the errorService.
 * 
 * @param {string} eventType - The type of event being dispatched.
 * @param {any} data - The payload or data associated with the event.
 * @param {DASHBOARD_TEAMS} [errorTeam=DASHBOARD_TEAMS.COMMON] - The team responsible for the error.
 * @param {DASHBOARD_PRIORITY_RANKS} [errorRank=DASHBOARD_PRIORITY_RANKS.P2] - The rank or severity level of the error.
 */
export const dispatchWebViewEvent = ({
  eventType,
  data,
  errorTeam = DASHBOARD_TEAMS.COMMON,
  errorRank = DASHBOARD_PRIORITY_RANKS.P2,
}: WebViewEventProps): void => {
  try {
    window.ReactNativeWebView?.postMessage?.(
      JSON.stringify({
        eventType,
        data,
      }),
    );
  } catch (error) {
    errorService.captureError(error, {
      tags: {
        team: errorTeam,
      },
      rank: errorRank,
    });
  }
};
