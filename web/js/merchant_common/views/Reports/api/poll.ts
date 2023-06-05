import longPoll from 'common/utils/poll/longPoll';
import { LongPollParamType, LongPollReturnType } from './types';

const FIVE_MINUTES = 5 * 60 * 1000; //5 minutes

export const reportsLongPoll = <T>({
  fetchFunc,
  validator,
  pollResSuccessCallback,
  pollResFailedCallback = () => {},
  onPollStopCallback = () => {},
}: LongPollParamType<T>): LongPollReturnType<T> => {
  let numberOfCalls = 0;
  const startTime = new Date() as unknown as number;
  let pollIntervalMultiplier = 3; // 3 seconds

  const poll = longPoll({
    fetchFunc: () =>
      fetchFunc()
        .then((res) => {
          pollIntervalMultiplier = 10;
          if (validator(res.data)) {
            pollResSuccessCallback(
              {
                ...res.data,
              },
              {
                polling: false,
              },
            );
          } else {
            pollResSuccessCallback(
              {
                ...res.data,
              },
              {
                polling: true,
              },
            );
          }
          return res.data;
        })
        .catch((err) => pollResFailedCallback({ err })),
    validator,
    getNextCallWaitime: () => {
      numberOfCalls++;

      const nextCallWaitTime = pollIntervalMultiplier * numberOfCalls * 1000;

      const timeElapsed = new Date() as unknown as number;

      if (timeElapsed - startTime > FIVE_MINUTES) {
        poll.abort();
        onPollStopCallback();
      }

      return nextCallWaitTime;
    },
  });

  return poll;
};
