import { getFormattedDate } from '../utils';

export default function KindOfReport({ createdAt, scheduleId }) {
  return (
    <div>
      {!!scheduleId ? (
        <label>Scheduled</label>
      ) : (
        <>
          <label>Requested</label>
          <p class="text-muted">{getRequestedAtTime(createdAt)}</p>
        </>
      )}
    </div>
  );
}

const SECONDS_IN_A_DAY = 86400;

function getRequestedAtTime(createdAt) {
  const nowInUnixTimeStamp = new Date().getTime() / 1000;
  if (nowInUnixTimeStamp - createdAt > SECONDS_IN_A_DAY) {
    return getFormattedDate(createdAt);
  } else {
    return moment(createdAt, 'X').fromNow();
  }
}
