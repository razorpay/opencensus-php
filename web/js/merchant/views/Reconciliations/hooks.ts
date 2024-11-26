import { useState, useEffect, useRef } from 'react';
import { useToast } from '@razorpay/blade/components';
import moment, { Moment } from 'moment';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

type UseReadTrackingProps = {
  objectName: string;
  screen: string;
  properties?: object;
  readDelay?: number;
};
interface DateRange {
  startDate: Moment;
  endDate: Moment;
}

const useReconTracking = ({
  objectName,
  screen,
  properties = {},
  readDelay = 15_000,
}: UseReadTrackingProps) => {
  useEffect(() => {
    analyticsTrackWithUserInfo({
      objectName,
      actionName: 'view',
      screen,
      properties,
    });
    // Will trigger read success after 15 seconds if the user is still on the page
    const timerId = setTimeout(() => {
      analyticsTrackWithUserInfo({
        objectName,
        actionName: 'read success',
        screen,
        properties,
      });
    }, readDelay);
    return () => clearTimeout(timerId);
  }, []);
};

const useCalendarRange = () => {
  const [dateRange, setDateRange] = useState<DateRange>({
    startDate: moment().subtract(7, 'days').startOf('day'),
    endDate: moment().endOf('day'),
  });

  const toast = useToast();

  const handleRangeChange = ({ startDate, endDate }): void => {
    if (startDate && endDate) {
      const diffInDays = endDate.diff(startDate, 'days');
      if (diffInDays > 7) {
        toast.show({
          content:
            'Currently max range allowed is 7 days. Please try again with shorter range of days',
          color: 'notice',
          autoDismiss: true,
        });
      } else {
        setDateRange({ startDate, endDate });
      }
    }
  };

  return {
    dateRange,
    handleRangeChange,
  };
};

const useScrollPosition = (ref: React.RefObject<HTMLElement>) => {
  const [scrollPosition, setScrollPosition] = useState<{ scrollLeft: number; scrollTop: number }>({
    scrollLeft: 0,
    scrollTop: 0,
  });

  const isScrolling = useRef<boolean>(false);

  const handleScroll = () => {
    if (ref.current) {
      const { scrollLeft, scrollTop } = ref.current;
      if (!isScrolling.current) {
        isScrolling.current = true;
        requestAnimationFrame(() => {
          setScrollPosition({ scrollLeft, scrollTop });
          isScrolling.current = false;
        });
      }
    }
  };

  useEffect(() => {
    const currentRef = ref.current;
    if (currentRef) {
      currentRef.addEventListener('scroll', handleScroll);
      handleScroll();

      return () => {
        currentRef.removeEventListener('scroll', handleScroll);
      };
    }

    return () => {};
  }, [ref]);

  return scrollPosition;
};

export { useCalendarRange, useReconTracking, useScrollPosition };
