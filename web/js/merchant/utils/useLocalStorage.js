import { useState } from 'react';
import errorService from '@razorpay/universe-utils/errorService';
import { Teams, Ranks } from 'common/new-ui/ErrorBoundary';

export default function useLocalStorage(key, initialValue) {
  const [storedValue, setStoredValue] = useState(() => {
    try {
      const item = window.localStorage.getItem(key);
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
      window.localStorage.setItem(key, JSON.stringify(valueToStore));
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
