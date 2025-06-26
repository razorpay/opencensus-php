import { useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { BentoCardData, RenderedBentoCard } from './types';

// Custom hook to get current deviceType
export function useDeviceType({
  shouldAddBigMobileSplit = false,
}: {
  shouldAddBigMobileSplit?: boolean;
} = {}) {
  const { theme } = useTheme();
  const { matchedBreakpoint } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const breakpoint = matchedBreakpoint || '';
  if (['l', 'xl'].includes(breakpoint)) {
    return 'desktop';
  } else if (['m'].includes(breakpoint)) {
    return 'tablet';
  } else {
    if (shouldAddBigMobileSplit && breakpoint === 's') {
      return 'bigMobile';
    }
    return 'mobile';
  }
}

// Divide an array into chunks of a given size
function chunkArray<T>(array: T[], size: number): T[][] {
  const result: T[][] = [];
  for (let i = 0; i < array.length; i += size) {
    result.push(array.slice(i, i + size));
  }
  return result;
}

export function getRows(deviceType: string, bentoCards: BentoCardData[]) {
  // Determine perRow
  let perRow = 1;
  if (deviceType === 'desktop') perRow = 3;
  else if (deviceType === 'tablet') perRow = 2;

  // Divide into rows
  const rows = chunkArray<BentoCardData>(bentoCards, perRow);

  const renderedCards: RenderedBentoCard[] = [];

  rows.forEach((row, rowIdx) => {
    const isLastRow = rowIdx === rows.length - 1;
    if (deviceType === 'desktop') {
      if (isLastRow && row.length === 2) {
        // Both large
        row.forEach((card, i) => {
          renderedCards.push({ ...card, boxType: 'large', span: 2, key: `${rowIdx}-${i}` });
        });
      } else {
        if (rowIdx % 2 === 0) {
          // Even row index: Large, Small, Small
          if (row.length === 3) {
            renderedCards.push(
              { ...row[0], boxType: 'large', span: 2, key: `${rowIdx}-0` },
              { ...row[1], boxType: 'small', span: 1, key: `${rowIdx}-1` },
              { ...row[2], boxType: 'small', span: 1, key: `${rowIdx}-2` },
            );
          } else {
            // Fallback for incomplete row
            row.forEach((card, i) => {
              renderedCards.push({ ...card, boxType: 'small', span: 1, key: `${rowIdx}-${i}` });
            });
          }
        } else {
          // Odd row index: Small, Small, Large
          if (row.length === 3) {
            renderedCards.push(
              { ...row[0], boxType: 'small', span: 1, key: `${rowIdx}-0` },
              { ...row[1], boxType: 'small', span: 1, key: `${rowIdx}-1` },
              { ...row[2], boxType: 'large', span: 2, key: `${rowIdx}-2` },
            );
          } else {
            // Fallback for incomplete row
            row.forEach((card, i) => {
              renderedCards.push({ ...card, boxType: 'small', span: 1, key: `${rowIdx}-${i}` });
            });
          }
        }
      }
    } else if (deviceType === 'tablet') {
      if (isLastRow && row.length === 1) {
        // Last row, single card: span full width
        renderedCards.push({
          ...row[0],
          boxType: 'large',
          span: 2,
          key: `${rowIdx}-0`,
          spanFullTablet: true,
        });
      } else {
        if (rowIdx % 2 === 0) {
          // Even row index: Large, Small
          if (row.length === 2) {
            renderedCards.push(
              { ...row[0], boxType: 'large', span: 2, key: `${rowIdx}-0` },
              { ...row[1], boxType: 'small', span: 1, key: `${rowIdx}-1` },
            );
          } else {
            // Fallback for incomplete row
            row.forEach((card, i) => {
              renderedCards.push({ ...card, boxType: 'large', span: 2, key: `${rowIdx}-${i}` });
            });
          }
        } else {
          // Odd row index: Small, Large
          if (row.length === 2) {
            renderedCards.push(
              { ...row[0], boxType: 'small', span: 1, key: `${rowIdx}-0` },
              { ...row[1], boxType: 'large', span: 2, key: `${rowIdx}-1` },
            );
          } else {
            // Fallback for incomplete row
            row.forEach((card, i) => {
              renderedCards.push({ ...card, boxType: 'large', span: 2, key: `${rowIdx}-${i}` });
            });
          }
        }
      }
    } else {
      // Mobile: all large
      row.forEach((card, i) => {
        renderedCards.push({ ...card, boxType: 'large', span: 1, key: `${rowIdx}-${i}` });
      });
    }
  });

  return renderedCards;
}

export function getGridTemplateColumns(deviceType: string) {
  if (deviceType === 'desktop') return 'repeat(4, 1fr)';
  if (deviceType === 'tablet') return 'repeat(3, 1fr)';
  return '1fr';
}

export function getGridColumn(deviceType: string, span: number, spanFullTablet?: boolean): string {
  if (deviceType === 'desktop') {
    return `span ${span}`;
  } else if (deviceType === 'tablet') {
    return spanFullTablet ? '1 / -1' : `span ${span}`;
  } else {
    return 'span 1';
  }
}

export const LOCAL_STORAGE_KEY = 'versioning_modal_auto_open';
export const TWENTY_FOUR_HOURS = 24 * 60 * 60 * 1000;
export const MAX_COUNT = 3; // for auto open on mount

type VersioningModalLocalStorageMetadata = { count: number; expiresAt: number } | null;

// used to track the number of times the modal has been opened (via auto open only)
function setLocalStorageMetadata(parsed: VersioningModalLocalStorageMetadata) {
  const newCount = parsed && typeof parsed.count === 'number' ? parsed.count + 1 : 1;
  const newExpiresAt = Date.now() + TWENTY_FOUR_HOURS;
  localStorage.setItem(
    LOCAL_STORAGE_KEY,
    JSON.stringify({ count: newCount, expiresAt: newExpiresAt }),
  );
}

export const deviceTypeToSectionHeadingSize = {
  mobile: 'large',
  bigMobile: '2xlarge',
  tablet: 'xlarge',
  desktop: '2xlarge',
};

// checks for manual click first (via query params), if not present, checks for auto open (via local storage)
export function shouldOpenVersioningModal(
  pathname: string,
  searchParams: URLSearchParams,
): { shouldOpen: boolean; isQueryParam?: boolean; runModalOpenSideEffects?: () => void } {
  try {
    let stored = localStorage.getItem(LOCAL_STORAGE_KEY);

    let parsed: VersioningModalLocalStorageMetadata = null;
    if (stored) {
      try {
        parsed = JSON.parse(stored);
      } catch {
        parsed = null;
      }
    }
    // if versioning modal query params are present, open modal (regardless of local storage values)
    const isVersioningModalQueryParams =
      pathname === '/dashboard' && searchParams.get('event') === 'versioning';
    if (isVersioningModalQueryParams) {
      return { shouldOpen: true, isQueryParam: true };
    }

    // if key missing in local storage, open modal
    if (!parsed) {
      return {
        shouldOpen: true,
        runModalOpenSideEffects: () => setLocalStorageMetadata(parsed)
      };
    }
    // if count is less than max count and expiresAt is in the past, open modal
    if (
      parsed &&
      typeof parsed.count === 'number' &&
      parsed.count < MAX_COUNT &&
      Date.now() > parsed.expiresAt
    ) {
      return {
        shouldOpen: true,
        runModalOpenSideEffects: () => setLocalStorageMetadata(parsed)
      };
    }
    return { shouldOpen: false };
  } catch {
    // in case of errors in accessing local storage (happens on webviews), return false
    return { shouldOpen: false };
  }
}
