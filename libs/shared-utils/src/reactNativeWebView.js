import errorService from '@razorpay/universe-utils/errorService';
import { Ranks, Teams } from './constants/errorBoundary';

export const dispatchWebViewEvent = ({
  eventType,
  data,
  errorTeam = Teams.COMMON,
  errorRank = Ranks.P2,
}) => {
  try {
    window.ReactNativeWebView.postMessage(
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
