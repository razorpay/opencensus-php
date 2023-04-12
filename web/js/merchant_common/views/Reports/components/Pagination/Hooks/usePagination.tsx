import { useMemo } from 'react';
import { arrayFromRange } from 'merchant_common/views/Reports/utils/commonUtils';

export const DOTS = '...';

export const usePagination = ({ totalCount, pageSize, siblingCount = 1, currentPage }) => {
  const totalPageCount = Math.ceil(totalCount / pageSize);

  const paginationRange = useMemo(() => {
    const shouldShowLeftDots = currentPage - siblingCount > 2;
    const shouldShowRightDots = currentPage + siblingCount < totalPageCount - 2;

    switch (true) {
      case !shouldShowLeftDots && !shouldShowRightDots:
        return arrayFromRange(1, totalPageCount);
      case !shouldShowLeftDots && shouldShowRightDots:
        return [...arrayFromRange(1, 3 + 2 * siblingCount), DOTS, totalPageCount];
      case shouldShowLeftDots && !shouldShowRightDots:
        return [
          1,
          DOTS,
          ...arrayFromRange(totalPageCount - (3 + 2 * siblingCount) + 1, totalPageCount),
        ];
      case shouldShowLeftDots && shouldShowRightDots:
        return [
          1,
          DOTS,
          ...arrayFromRange(currentPage - siblingCount, currentPage + siblingCount),
          DOTS,
          totalPageCount,
        ];
      default:
        return arrayFromRange(1, totalPageCount);
    }
  }, [currentPage, totalPageCount]);

  if (currentPage < 0) {
    return { paginationRange: [], totalPageCount: 0 };
  } else {
    return { paginationRange, totalPageCount };
  }
};
