import { merchantFetch } from 'merchant/utils/ajax';

import { STATUS } from 'merchant/views/Account/TrustedBadge/constants/data';
import { set, merge } from 'common/utils/immutable';

/** Start of CONSTANTS */
const FETCH_TRUSTED_BADGE_STATUS = 'FETCH_TRUSTED_BADGE_STATUS';
const UPDATE_RTB_MERCHANT_STATUS = 'UPDATE_RTB_MERCHANT_STATUS';

/** End of CONSTANTS */

/** Start of Actions */

export const fetchTrustedBadgeStatus = () => {
  return {
    type: FETCH_TRUSTED_BADGE_STATUS,
    payload: merchantFetch({
      url: 'trusted_badge',
      mode: 'live',
    }),
  };
};

export const updateRTBMerchantStatus = (status) => {
  return {
    type: UPDATE_RTB_MERCHANT_STATUS,
    payload: merchantFetch({
      url: 'trusted_badge/merchant_status',
      method: 'PUT',
      mode: 'live',
      data: {
        merchant_status: status,
      },
    }),
    status,
  };
};

/** End of Actions */

function findTypeFromResponse(response) {
  const returnData = { original: response };
  // calculate case based on response
  const { merchant_status: mStatus, status, is_delisted_atleast_once: isDelisted } = response;
  if (status === 'eligible') {
    if (mStatus === 'optout') {
      returnData.badgeStatus = STATUS.YES_ELIGIBLE_OPTED_OUT;
    } else {
      returnData.badgeStatus = STATUS.YES_ELIGIBLE_LIVE;
    }
  } else if (status === 'ineligible') {
    // not eligible
    if (mStatus === 'waitlist' && isDelisted === 0) {
      returnData.badgeStatus = STATUS.NOT_ELIGIBLE_DELISTED_YES_WAITLISTED;
    } else if (isDelisted === 1) {
      returnData.badgeStatus = STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED;
    } else {
      returnData.badgeStatus = STATUS.NOT_ELIGIBLE_WAITLISTED_DELISTED;
    }
  } else {
    // blacklisted
    returnData.badgeStatus = STATUS.NOT_ELIGIBLE_YES_WAITLISTED_DELISTED;
  }
  return returnData;
}

/** Start of Reducer */

const initialState = {
  status: false,
  loading: true,
};

export default function trustedBadgeReducer(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_TRUSTED_BADGE_STATUS}::PENDING`:
      return set(state, 'loading', true);
    case `${FETCH_TRUSTED_BADGE_STATUS}::SUCCESS`:
      return merge(state, {
        status: findTypeFromResponse(action.payload.data),
        loading: false,
      });
    case `${FETCH_TRUSTED_BADGE_STATUS}::ERROR`:
      return merge(state, {
        error: true,
        loading: false,
      });
    case `${UPDATE_RTB_MERCHANT_STATUS}::PENDING`:
      return set(state, 'updatePending', true);
    case `${UPDATE_RTB_MERCHANT_STATUS}::SUCCESS`: {
      const { status } = action || {};
      let newStatus = state.status.badgeStatus;
      if (status === 'optout') {
        newStatus = STATUS.YES_ELIGIBLE_OPTED_OUT;
      } else if (status === 'optin') {
        newStatus = STATUS.YES_ELIGIBLE_LIVE;
      } else if (status === 'waitlist') {
        newStatus = STATUS.NOT_ELIGIBLE_DELISTED_YES_WAITLISTED;
      }
      return merge(state, {
        status: { ...state.status, badgeStatus: newStatus },
        updatePending: false,
        updateAction: status,
      });
    }
    case `${UPDATE_RTB_MERCHANT_STATUS}::ERROR`:
      return merge(state, {
        updateError: true,
        updatePending: false,
        updateAction: action.status,
      });
    default:
      return state;
  }
}

/** End of Reducer */
