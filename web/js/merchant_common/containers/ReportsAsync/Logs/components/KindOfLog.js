import { getFormattedDate } from '../../utils';

export default function KindOfReport({ createdAt, scheduleId }) {
  return (
    <div>
      {!!scheduleId ? (
        <strong>Scheduled</strong>
      ) : (
        <>
          <strong>Requested</strong>
          <p class="text-muted text-small">{getRequestedAtTime(createdAt)}</p>
        </>
      )}
    </div>
  );
}

const SECONDS_IN_A_DAY = 86400;
const NO_OF_MS_IN_A_SEC = 1000;
function getRequestedAtTime(createdAt) {
  const nowInUnixTimeStamp = new Date().getTime() / NO_OF_MS_IN_A_SEC; //to convert time from ms to sec

  if (nowInUnixTimeStamp - createdAt > SECONDS_IN_A_DAY) {
    return getFormattedDate(createdAt);
  } else {
    return moment(createdAt, 'X').fromNow();
  }
}
