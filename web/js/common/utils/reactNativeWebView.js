// Todo: delete this file, it's available in @dashboard/shared-utils
import errorService from '@razorpay/universe-utils/errorService';
import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

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
