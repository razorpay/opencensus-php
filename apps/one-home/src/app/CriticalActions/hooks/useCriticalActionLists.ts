import { useMemo } from 'react';
import { useBreakpoint, useTheme } from '@razorpay/blade/utils';
import { CriticalActionComponent, CriticalActions } from '../types';

const useCriticalActionLists = (criticalActionsData: CriticalActions, isMobile: boolean) => {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const criticalActionsList = useMemo(() => {
    return criticalActionsData?.components ?? [];
  }, [criticalActionsData?.components]);

  const criticalActionsToShow: CriticalActionComponent[] = useMemo(() => {
    if (isMobile) return criticalActionsList;
    const cardCount: Record<string, number> = { m: 2, l: 2, xl: 3 };
    if (criticalActionsList.length > 0) {
      return criticalActionsList.slice(
        0,
        cardCount[matchedBreakpoint as keyof typeof cardCount] || 3,
      );
    }
    return [];
  }, [matchedBreakpoint, isMobile, criticalActionsList]);

  const drawerCriticalActions = useMemo(() => {
    if (criticalActionsList.length > 0) {
      return criticalActionsList;
      // return criticalActionsList.filter(
      //   (item: CriticalActionComponent) =>
      //     !criticalActionsToShow.some((itemToShow) => itemToShow?.id === item?.id),
      // );
    }
    return [];
  }, [criticalActionsList, criticalActionsToShow]);

  return { criticalActionsList, criticalActionsToShow, drawerCriticalActions };
};

export default useCriticalActionLists;
