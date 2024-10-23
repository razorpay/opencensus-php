import moment from 'moment';

import { getItem, setItem } from 'common/utils/localStorage';
import store from 'merchant/store';
import ajax from 'merchant/utils/ajax';

export const getAutomaticSettlementTime = () => {
  const currentHour = Number(moment().format('HH'));

  if (currentHour < 17 && currentHour > 9) return '5 PM';
  return '9 AM';
};

export const enableAutomaticSettlements = () =>
  ajax(
    {
      url: 'es/scheduled',
      method: 'POST',
    },
    {},
    '/merchant/api',
  );

export const getEsPartialAutomaticDateKey = () => {
  const {
    session: { user },
  } = store.getState();

  return `ENABLE_ES_PARTIAL_AUTOMATIC_DATE-${user.id}`;
};

export const setEnableEsPartialAutomaticDate = () => {
  setItem(getEsPartialAutomaticDateKey(), moment().format('DD/MM/YYYY'));
};

export const getEnableEsPartialAutomaticDate = () => {
  return getItem(getEsPartialAutomaticDateKey());
};

export const getNoOfDaysAfterEsPartialEnable = () => {
  const enableDate = getEnableEsPartialAutomaticDate();
  return enableDate && moment().diff(moment(enableDate, 'DD/MM/YYYY'), 'days');
};

export const getEsBannerKey = (bannerType) => {
  const {
    session: { user },
  } = store.getState();

  return `SEEN_ES_BANNER-${bannerType}-${user.id}`;
};

export const getEsBannerSeen = (bannerType) => {
  return getItem(getEsBannerKey(bannerType));
};

export const setEsBannerSeen = (bannerType, value) => {
  setItem(getEsBannerKey(bannerType), value ?? true);
};

export const getEsNudgeKey = (nudgeType) => {
  const {
    session: { user },
  } = store.getState();

  return `SEEN_ES_NUDGE-${nudgeType}-${user.id}`;
};

export const getEsNudgeSeen = (nudgeType) => {
  return getItem(getEsNudgeKey(nudgeType));
};

export const setEsNudgeSeen = (nudgeType) => {
  setItem(getEsNudgeKey(nudgeType), true);
};
