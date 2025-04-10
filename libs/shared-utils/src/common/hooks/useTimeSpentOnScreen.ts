import { useEffect, useRef, useState, useCallback } from 'react';
import { useLocation } from 'react-router-dom';
import moment, { Moment } from 'moment';

/**
 * Custom hook to measure the time spent on a specific route/page.
 * It resets the timer when the route changes.
 * @returns {Object} Object containing timeSpent and a callback to register the time spent
 */
export const useTimeSpentOnScreen = () => {
  const location = useLocation();
  const startTimeRef = useRef<Moment>(moment());
  const [timeSpent, setTimeSpent] = useState<number>(0);
  const currentRouteRef = useRef<string>(location.pathname);

  useEffect(() => {
    if (currentRouteRef.current !== location.pathname) {
      currentRouteRef.current = location.pathname;
    }
    startTimeRef.current = moment().clone();

    return () => {
      const endTime = moment().clone();
      const timeSpentInSeconds = endTime.diff(startTimeRef.current, 'seconds');
      setTimeSpent(timeSpentInSeconds);
    };
  }, [location.pathname]);

  const registerTimeSpent = useCallback(() => {
    const endTime = moment().clone();
    const timeSpentInSeconds = endTime.diff(startTimeRef.current, 'seconds');
    setTimeSpent(timeSpentInSeconds);
    return {
      timeSpent: timeSpentInSeconds,
      route: currentRouteRef.current,
    };
  }, []);

  return {
    timeSpent,
    registerTimeSpent,
    currentRoute: currentRouteRef.current,
  };
};
