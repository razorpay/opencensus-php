import { getFormattedDate } from 'merchant_common/containers/ReportsAsync/utils';
import moment from 'moment';
import React from 'react';

export default function KindOfReport({ createdAt, scheduleId }) {
  return (
    <div>
      {!!scheduleId ? (
        <strong>Scheduled</strong>
      ) : (
        <>
          <strong>Requested</strong>
          <p className="text-muted text-small">{getRequestedAtTime(createdAt)}</p>
        </>
      )}
    </div>
  );
}

export const SECONDS_IN_A_DAY = 86400;
export const NO_OF_MS_IN_A_SEC = 1000;
function getRequestedAtTime(createdAt) {
  const nowInUnixTimeStamp = new Date().getTime() / NO_OF_MS_IN_A_SEC; //to convert time from ms to sec

  if (nowInUnixTimeStamp - createdAt > SECONDS_IN_A_DAY) {
    return getFormattedDate(createdAt);
  } else {
    return moment(createdAt, 'X').fromNow();
  }
}
