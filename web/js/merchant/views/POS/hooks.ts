import { RefObject, useEffect, useState } from 'react';
import { useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { useQuery } from '@tanstack/react-query';

import { CHECKOUT_ERRORS } from './constants';
import { getLatestOrder } from './services';
import { CheckoutValidationError, OrderDetailsItem } from './types';

type breakpoints = string | undefined;

type useBladeBreakpoints = {
  matchedBreakpoint: breakpoints;
  isMobile: boolean;
  isDesktop: boolean;
  isLargeScreen: boolean; //desktop and tablets
};

export const useBladeBreakpoints = (): useBladeBreakpoints => {
  const { theme } = useTheme();
  const { matchedBreakpoint, matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });

  return {
    matchedBreakpoint,
    isMobile: matchedDeviceType === 'mobile',
    isDesktop: matchedDeviceType === 'desktop',
    isLargeScreen: matchedBreakpoint === 'l' || matchedBreakpoint === 'xl',
  };
};

type UseExecuteAfterDelay = {
  callback: () => void;
  delay: number;
};

export const useExecuteAfterDelay = ({ callback, delay }: UseExecuteAfterDelay): void => {
  useEffect(() => {
    const timer = setTimeout(() => {
      callback();
    }, [delay]);

    return () => {
      clearTimeout(timer);
    };
  }, [delay, callback]);
};

type UseIsVisibleProps = {
  ref: RefObject<HTMLElement>;
  initialValue: boolean;
  visibilityChangeCallback?: (isVisible: boolean) => void;
};

export const useIsVisible = ({
  ref,
  initialValue,
  visibilityChangeCallback,
}: UseIsVisibleProps): boolean => {
  const [isVisible, setIsVisible] = useState<boolean>(initialValue ?? false);

  const handleVisibilityChange = (entries: IntersectionObserverEntry[]) => {
    const entry = entries[0];
    if (entry.isIntersecting) {
      setIsVisible(() => true);
    } else {
      setIsVisible(() => false);
    }
    visibilityChangeCallback?.(entry.isIntersecting);
  };

  useEffect(() => {
    const targetEl = ref.current;
    if (!targetEl) return undefined;

    const observer = new IntersectionObserver(handleVisibilityChange, { threshold: 1 });
    observer.observe(targetEl);

    return () => {
      observer.disconnect();
    };
  }, [ref]);

  return isVisible;
};

type UseLatestOrder = {
  latestOrder: OrderDetailsItem | undefined;
  isLatestOrderLoading: boolean;
  latestOrderFetchError: CheckoutValidationError | null;
  refetchLatestOrder: () => void;
};

export const useLatestOrder = (): UseLatestOrder => {
  const {
    data: latestOrderFetched,
    isLoading: isLatestOrderLoading,
    isError,
    refetch: refetchLatestOrder,
  } = useQuery(['pos', 'latest-order'], {
    queryFn: getLatestOrder,
    retry: false,
    cacheTime: 1000 * 60 * 1,
    staleTime: Infinity,
    refetchOnWindowFocus: false,
    refetchOnMount: false,
  });

  const latestOrder = latestOrderFetched ?? null;

  return {
    latestOrder: latestOrder?.data,
    isLatestOrderLoading,
    latestOrderFetchError: !!isError ? CHECKOUT_ERRORS.ORDER_CREATE_FAILED : null,
    refetchLatestOrder,
  };
};
