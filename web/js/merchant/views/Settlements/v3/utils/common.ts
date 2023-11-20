import { SpiltzContextState } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { SettlementsCollectionReducerState, User } from 'common/typings';
import { DateInfo, ERROR_TYPE, SettlementListFilters } from 'merchant/views/Settlements/v3/typings';
import moment from 'moment';
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

  if (!abExperiments?.settlementsV3_details_revamp) return false;
  if (user.isOrgCurlec) {
    return false;
  }
  return isExperimentEnabled(abExperiments.settlementsV3_details_revamp) && user.isOrgRZP;
};
