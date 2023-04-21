import moment from 'moment';

const KEY = 'universal-search-ftux';
const MAX_VISIBILITY = 3;

export const isVisible = (): boolean => {
  const visibilityStatus = localStorage.getItem(KEY);
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
  localStorage.setItem(
    KEY,
    JSON.stringify({
      count,
      expireAt: moment().add(1, 'days').format(),
    }),
  );
};

export const hideFtux = ({ onGotIt, onFocus }: { onGotIt?: boolean; onFocus?: boolean }): void => {
  const visibilityStatus = localStorage.getItem(KEY);
  if (visibilityStatus) {
    const { count, expireAt } = JSON.parse(visibilityStatus);
    if (count > MAX_VISIBILITY) return;
    if (onGotIt || (onFocus && moment().isAfter(expireAt))) {
      setCount({ count: count + 1 });
    }
  } else if (onGotIt) {
    setCount({ count: 1 });
  }
};
