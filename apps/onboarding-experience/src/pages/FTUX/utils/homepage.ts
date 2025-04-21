import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import {
  NO_CODE_PAGE_LAYOUT,
  PG_PAGE_LAYOUT,
  PG_PLUS_NO_CODE_PAGE_LAYOUT,
} from '@FTUX/constants/homepage';

interface LayoutOptions {
  isPgMerchant: boolean;
  isNoCodeMerchant: boolean;
  hasWebsite: boolean;
}

/**
 * Determines which layout configuration to use based on merchant type and website status
 *
 * The function selects the appropriate homepage layout based on:
 * 1. If merchant uses both PG and no-code solutions or has a website -> combined layout
 * 2. If merchant only uses PG -> PG-specific layout
 * 3. If merchant only uses no-code solutions -> no-code specific layout
 */
export const getLayoutByMerchantType = ({
  isPgMerchant,
  isNoCodeMerchant,
  hasWebsite,
}: LayoutOptions): HOMEPAGE_ELEMENTS[] => {
  if ((isPgMerchant && isNoCodeMerchant) || hasWebsite) {
    return [...PG_PLUS_NO_CODE_PAGE_LAYOUT];
  }
  if (isPgMerchant) {
    return [...PG_PAGE_LAYOUT];
  }
  if (isNoCodeMerchant) {
    return [...NO_CODE_PAGE_LAYOUT];
  }
  return [];
};
