import moment from 'moment';

import { NavLinkData } from 'merchant/components/SidebarV2/typings';

const KEY = 'left_nav_items_cache';
const EXPIRY = 1; // in days

export const getLeftNavItemsCache = ({ merchantId }): NavLinkData[] | null => {
  const leftNavItemsCache = window?.localStorage.getItem(`${KEY}_${merchantId}`);
  if (!leftNavItemsCache) {
    return null;
  }
  try {
    const { data, expireAt } = JSON.parse(leftNavItemsCache);
    if (expireAt && moment().isAfter(expireAt)) {
      return null;
    }
    return data;
  } catch {
    // no action is required
  }
  return null;
};

export const setLeftNavItemsCache = ({
  merchantId,
  leftNavItems,
}: {
  merchantId: string;
  leftNavItems: NavLinkData[];
}) => {
  window?.localStorage.setItem(
    `${KEY}_${merchantId}`,
    JSON.stringify({
      data: leftNavItems,
      expireAt: moment().add(EXPIRY, 'days').format(),
    }),
  );
};
