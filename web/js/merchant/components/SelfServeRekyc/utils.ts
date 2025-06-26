import { moment } from './moment';
import { User } from 'common/typings';
import { isExperimentEnabled } from 'common/splitz/utils';
import { SELF_SERVE_REKYC_HIDE_MODAL, STATUSES_TO_SHOW_MODAL } from 'merchant/components/SelfServeRekyc/constants';
import { camelize } from '@libs/shared-utils';
export const getDaysFromDeadline = (deadline: number | undefined): number => {
  if(!deadline){
    return 0;
  }

  const deadlineDate = moment.unix(deadline).startOf('day');
  const todayDate = moment().startOf('day');

  return deadlineDate.diff(todayDate, 'days');
};

export const getTimelineString = (deadline: number): string => {
  const dayCount = getDaysFromDeadline(deadline);

  switch(true){
    case dayCount <= 90 && dayCount >= 31:
      return 'firstThirtyDays';
    case dayCount <=30 && dayCount >= 15:
      return 'thirtyToFifteenDays';
    case dayCount <= 14 && dayCount >= 0:
      return 'foh';
    case dayCount < 0:
      return 'liveDisabled'
    default:
      return 'firstThirtyDays';
  }
}

export const getDeadlineDate = (deadline: undefined | number) => {
  if(!deadline){
    return '';
  }

  return moment(deadline * 1000).format('Do MMMM');
}

export const isEligibleForSelfServeRekyc = (splitz, user: Partial<User>) => {
  return (
    isExperimentEnabled(splitz?.abExperiments?.enable_self_serve_rekyc) &&
    user.isCountryIndia &&
    user.isOrgRZP &&
    user.isActivated
  );
};

export const shouldShowModal = (deadline: number) => {
  const daysLeft = getDaysFromDeadline(deadline);

  const isModalConfigAvailable = localStorage.getItem(SELF_SERVE_REKYC_HIDE_MODAL);

  if(!isModalConfigAvailable){
    return true;
  }

  if (daysLeft > 60) {
    // Show once every 14 days
    return daysLeft % 14 === 0;
  } else if (daysLeft > 14) {
    // Show twice a week (~every 3 days)
    return daysLeft % 3 === 0;
  } else {
    // Show daily
    return true;
  }
}

// modal opening logic depends on 
export function getRekycModalIsOpen(rekycStatus: string, deadline: number): boolean {
  if (STATUSES_TO_SHOW_MODAL.includes(rekycStatus)) {
    if (shouldShowModal(deadline)) {
      const isModalConfigAvailable = localStorage.getItem(SELF_SERVE_REKYC_HIDE_MODAL);
      if (isModalConfigAvailable) {
        localStorage.removeItem(SELF_SERVE_REKYC_HIDE_MODAL);
      }
      return true;
    }
  }
  return false;
}

export function getRekycStatus(status: string) {
  const statusString = status?.split('_')?.join(' ');
  return camelize(statusString || '');
}