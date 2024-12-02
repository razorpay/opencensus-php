import moment from 'moment';

import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { SettlementsCollectionReducerState, User } from 'common/typings';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { DateInfo, ERROR_TYPE, SettlementListFilters } from 'merchant/views/Settlements/v3/typings';
const EMPTY_STATE_KEY = 'settlements_empty_state';

export const getSettlementDate = (timestamp: number): DateInfo => {
  if (!timestamp) return { date: '---', time: '---' };
  const date = moment.unix(timestamp);
  return {
    date: `${date.format('ddd')} ${date.format('MMM')} ${date.format('DD')},`,
    time: date.format('LT'),
  };
};

export const getErrorType = (error: string): ERROR_TYPE => {
  if (
    error &&
    (error.includes('not a valid id') || error.includes('The id provided does not exist'))
  ) {
    return ERROR_TYPE.INVALID_ID;
  }
  return ERROR_TYPE.SERVER_ERROR;
};

// Todo: delete this function, it's available in @dashboard/shared-utils
export const validateUnixTimestamp = (timestamp: string): number | null => {
  const unixTime = parseInt(timestamp, 10);
  if (moment(unixTime).isValid()) {
    return unixTime;
  }
  return null;
};

export const showPaymentProviderColumn = (user: User): boolean =>
  user.isSingleReconEnabled && user.isOptimizerEnabled;

export const validateSettlementIdFilters = (
  settlements: SettlementsCollectionReducerState['items'],
  filters: SettlementListFilters,
): SettlementsCollectionReducerState['items'] => {
  const { status, from, to, utr } = filters;

  return settlements.filter((settlement) => {
    if (status && settlement.status !== status) {
      return false;
    }

    if (from && to) {
      const _from = parseInt(from, 10);
      const _to = parseInt(to, 10);
      if (!(settlement.created_at >= _from && settlement.created_at <= _to)) {
        return false;
      }
    }

    if (utr && settlement.utr !== utr) {
      return false;
    }

    return true;
  });
};

export const isEmptyStateVisible = (): boolean => {
  const visibilityStatus = localStorage.getItem(EMPTY_STATE_KEY);
  if (!visibilityStatus) {
    return true;
  }
  const { expireAt } = JSON.parse(visibilityStatus);
  if (expireAt && moment().isBefore(expireAt)) {
    return false;
  }
  return true;
};

export const hideEmptyState = (): void => {
  localStorage.setItem(
    EMPTY_STATE_KEY,
    JSON.stringify({
      expireAt: moment().add(1, 'days').format(),
    }),
  );
};

export const isSettlementsV3detailsRevamp = (splitz: SpiltzContextState, user: User): boolean => {
  const { abExperiments } = splitz || {
    abExperiments: { settlementsV3_details_revamp: undefined },
  };

  const isExcludedSegment = !user.isOrgRZP;

  if (isExcludedSegment) {
    const isSettlementsEnabled = isExperimentEnabled(
      abExperiments?.ramp_settlements_for_excluded_segment,
    );
    return isSettlementsEnabled;
  }

  if (!abExperiments?.settlementsV3_details_revamp) return false;

  return isExperimentEnabled(abExperiments.settlementsV3_details_revamp) && user.isOrgRZP;
};

export const trackSettlmentDetailsCopied = ({ type }): void => {
  analyticsTrackWithUserInfo({
    objectName: `Settlements ${type}`,
    actionName: 'Copied',
    screen: 'Settlements',
    properties: {
      page: 'Details View',
      settlements_experiment_name: 'v2',
      sessionId: window?.session_id ? window.session_id : undefined,
      isDetailsRevampFlow: true,
    },
  });
};

export const trackSettlmentDetailsContactSupport = (): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Settlement Details Create Ticket',
    actionName: 'Clicked',
    screen: 'Settlements',
    properties: {
      page: 'Details View',
      settlements_experiment_name: 'v2',
      sessionId: window?.session_id ? window.session_id : undefined,
      title: 'Contact support',
      isDetailsRevampFlow: true,
    },
  });
};

export const trackSettlmentDetailsUpdateBankAccount = (): void => {
  analyticsTrackWithUserInfo({
    objectName: 'Settlement Details Update Bank Account',
    actionName: 'Clicked',
    screen: 'Settlements',
    properties: {
      page: 'Details View',
      settlements_experiment_name: 'v2',
      sessionId: window?.session_id ? window.session_id : undefined,
      title: 'Update Bank account',
      isDetailsRevampFlow: true,
    },
  });
};
