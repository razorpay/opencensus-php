import longPoll from 'common/utils/poll/longPoll';
import { isLogInProgress } from 'merchant_common/containers/ReportsAsync/utils';

import BaseReportingEntity from './BaseReportingEntity';

const FIVE_MINUTES = 5 * 60 * 1000; //5 minutes
const FIFTEEN_MINUTES = FIVE_MINUTES * 3;

export default class Log extends BaseReportingEntity {
  resourceUrl = 'reporting/logs';

  poll(logId, onPollInitiated) {
    let numberOfCalls = 0;
    const startTime = new Date();
    let pollIntervalMultiplier = 3; // 3 seconds

    const { promise, abort } = longPoll({
      fetchFunc: () =>
        this.fetch(logId).then((response) => {
          pollIntervalMultiplier = getPollIntervalMultiplier(response.created_at);
          return response;
        }),
      // when sent true from validator polling will stop
      // continue polling if status is in progress
      validator: (log) => !isLogInProgress(log.status),
      getNextCallWaitime: () => {
        numberOfCalls++;

        const nextCallWaitTime = pollIntervalMultiplier * numberOfCalls * 1000;

        const timeElapsed = new Date();

        if (timeElapsed - startTime > FIVE_MINUTES) {
          abort();
        }

        return nextCallWaitTime;
      },
    });

    if (onPollInitiated) {
      onPollInitiated({ abort });
    }

    return promise;
  }
}

// returned value is number of seconds
// for 5 minutes old Log - 3 seconds
// for Log between 5 and 15 min - 5 seconds
// for log older than 15 min - 10 seconds
function getPollIntervalMultiplier(createdAtInSecs) {
  const createdAtInMs = createdAtInSecs * 1000;
  const reportCreatedFromNow = new Date() - createdAtInMs;
  if (reportCreatedFromNow < FIVE_MINUTES) {
    return 3;
  } else if (reportCreatedFromNow >= FIVE_MINUTES && reportCreatedFromNow < FIFTEEN_MINUTES) {
    return 5;
  } else {
    return 10;
  }
}
