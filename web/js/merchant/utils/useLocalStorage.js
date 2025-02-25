import { useState } from 'react';
import errorService from '@razorpay/universe-cli/errorService';
import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import { getItem, setItem } from 'common/utils/localStorage';

export default function useLocalStorage(key, initialValue) {
  const [storedValue, setStoredValue] = useState(() => {
    try {
      const item = getItem(key);
      return item ? JSON.parse(item) : initialValue;
    } catch (error) {
      errorService.captureError(error, {
        tags: {
          team: Teams.COMMON,
        },
        rank: Ranks.P2,
      });
      return initialValue;
    }
  });
  const setValue = (value) => {
    try {
      const valueToStore = value instanceof Function ? value(storedValue) : value;
      setStoredValue(valueToStore);
      setItem(key, JSON.stringify(valueToStore));
    } catch (error) {
      errorService.captureError(error, {
        tags: {
          team: Teams.COMMON,
        },
        rank: Ranks.P2,
      });
    }
  };

  return [storedValue, setValue];
}
