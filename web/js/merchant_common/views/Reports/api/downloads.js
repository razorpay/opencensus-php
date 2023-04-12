// Migration to ts for this file in TODO

import { merchantFetch } from 'merchant/utils/ajax';
import longPoll from 'common/utils/poll/longPoll';

export const fetchDownloadLogs = ({ page = 1, filter = '' }, headers) => {
  const [type, ...value] = filter.split('.');

  const LOGS_COUNT = 20;

  const paramHeader =
    type === 'header'
      ? {
          ...headers,
          [value[0]]: value[1],
        }
      : headers;

  const filterParams = type === 'param' ? value : '';

  const paginationParams = `limit=${LOGS_COUNT}&offset=${LOGS_COUNT * (page - 1)}`;

  return merchantFetch({
    url: `reporting/logs?${paginationParams}${filterParams}`,
    headers: paramHeader,
  });
};

export const fetchLogDetail = (logId, accountId, headers) => {
  return merchantFetch({
    url: `reporting/logs/${logId}`,
    ...(!!accountId && { accountId }),
    headers,
  });
};

const FIVE_MINUTES = 5 * 60 * 1000; //5 minutes
const FIFTEEN_MINUTES = FIVE_MINUTES * 3;

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

const logProcessingStatuses = ['created', 'processing'];
export const isLogInProgress = (logStatus) => logProcessingStatuses.includes(logStatus);

export const reportsLongPoll = ({
  fetchFunc = () => {},
  validator = () => {},
  // returned res will contain a flag "polling"
  pollResSuccessCallback = () => {},
  // returns error
  pollResFailedCallback = () => {},
  onPollStopCallback = () => {},
}) => {
  let numberOfCalls = 0;
  const startTime = new Date();
  let pollIntervalMultiplier = 3; // 3 seconds

  const poll = longPoll({
    fetchFunc: () =>
      fetchFunc()
        .then((res) => {
          pollIntervalMultiplier = getPollIntervalMultiplier(res.data.created_at);
          if (validator(res.data)) {
            pollResSuccessCallback({
              ...res.data,
              polling: false,
            });
          } else {
            pollResSuccessCallback({
              ...res.data,
              polling: true,
            });
          }
          return res.data;
        })
        .catch((err) => pollResFailedCallback({ err })),
    validator,
    getNextCallWaitime: () => {
      numberOfCalls++;

      const nextCallWaitTime = pollIntervalMultiplier * numberOfCalls * 1000;

      const timeElapsed = new Date();

      if (timeElapsed - startTime > FIVE_MINUTES) {
        poll.abort();
        onPollStopCallback();
      }

      return nextCallWaitTime;
    },
  });

  return poll;
};

// for array of logs
export const initiateLogsPoll = ({
  queryParams = {},
  headers = {},
  pollResSuccessCallback = () => {},
  pollResFailedCallback = () => {},
  onPollStopCallback = () => {},
}) => {
  return reportsLongPoll({
    fetchFunc: () => fetchDownloadLogs(queryParams, headers),
    // when sent true from validator polling will stop
    // continue polling if status is in progress
    validator: (data) => data?.items.findIndex((log) => isLogInProgress(log.status)) === -1,
    pollResSuccessCallback,
    pollResFailedCallback,
    onPollStopCallback,
  });
};

// for a single log
export const initiateLogPoll = ({
  logId,
  accountId,
  headers = {},
  pollResSuccessCallback = () => {},
  pollResFailedCallback = () => {},
  onPollStopCallback = () => {},
}) => {
  return reportsLongPoll({
    fetchFunc: () => fetchLogDetail(logId, accountId, headers),
    // when sent true from validator polling will stop
    // continue polling if status is in progress
    validator: (log) => !isLogInProgress(log.status),
    pollResSuccessCallback,
    pollResFailedCallback,
    onPollStopCallback,
  });
};
