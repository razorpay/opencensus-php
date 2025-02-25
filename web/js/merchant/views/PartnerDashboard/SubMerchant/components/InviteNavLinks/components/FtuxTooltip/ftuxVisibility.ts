import moment from 'moment';

const KEY = 'partnerships-invite-flow-tooltip';
const MAX_VISIBILITY = 3;

export const isVisible = (): boolean => {
  const visibilityStatus = window?.localStorage.getItem(KEY);
  if (!visibilityStatus) {
    return true;
  }
  const { count, expireAt } = JSON.parse(visibilityStatus);
  if (count >= MAX_VISIBILITY) {
    return false;
  }
  if (expireAt && moment().isBefore(expireAt)) {
    return false;
  }
  return true;
};

const setCount = ({ count }) => {
  window?.localStorage.setItem(
    KEY,
    JSON.stringify({
      count,
      expireAt: moment().add(1, 'days').format(),
    }),
  );
};

export const hideFtux = (): void => {
  const visibilityStatus = window?.localStorage.getItem(KEY);
  if (visibilityStatus) {
    const { count, expireAt } = JSON.parse(visibilityStatus);
    if (count > MAX_VISIBILITY) return;
    if (moment().isAfter(expireAt)) setCount({ count: count + 1 });
  } else setCount({ count: 1 });
};
