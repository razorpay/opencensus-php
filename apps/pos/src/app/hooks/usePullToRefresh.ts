import { useCallback, useRef, useState } from 'react';

export const usePullToRefresh = (threshold: number = 120) => {
  const [refreshing, setRefreshing] = useState(false);
  const touchStartY = useRef<number | null>(null);
  const touchMoveY = useRef<number>(0);

  const handleRefresh = useCallback(() => {
    setRefreshing(true);
    window.location.reload();
  }, []);

  const setupPullToRefresh = useCallback(
    (containerElement: HTMLDivElement | null, pullElement: HTMLDivElement | null) => {
      if (!containerElement || !pullElement) return () => {};

      const handleTouchStart = (e: TouchEvent) => {
        // Only enable pull to refresh when at top of page
        if (window.scrollY === 0) {
          touchStartY.current = e.touches[0].clientY;
        }
      };

      const handleTouchMove = (e: TouchEvent) => {
        if (touchStartY.current !== null) {
          touchMoveY.current = e.touches[0].clientY - touchStartY.current;
          // Add some resistance to the pull
          if (touchMoveY.current > 0) {
            e.preventDefault();
            requestAnimationFrame(() => {
              const pullElementTop = window.getComputedStyle(pullElement).top;
              pullElement.style.transform = `translateY(${Math.abs(parseInt(pullElementTop))}px)`;
            });
          }
        }
      };

      const handleTouchEnd = () => {
        if (touchMoveY.current > threshold) {
          handleRefresh();
        } else {
          pullElement.style.transform = `translateY(0px)`;
        }
        touchStartY.current = null;
        touchMoveY.current = 0;
      };

      containerElement.addEventListener('touchstart', handleTouchStart, { passive: false });
      containerElement.addEventListener('touchmove', handleTouchMove, { passive: false });
      containerElement.addEventListener('touchend', handleTouchEnd);
      containerElement.style.transition = 'all 500ms ease';

      return () => {
        containerElement.removeEventListener('touchstart', handleTouchStart);
        containerElement.removeEventListener('touchmove', handleTouchMove);
        containerElement.removeEventListener('touchend', handleTouchEnd);
      };
    },
    [refreshing, threshold],
  );

  return { refreshing, setupPullToRefresh };
};
