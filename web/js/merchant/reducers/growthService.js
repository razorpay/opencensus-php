import GrowthService from 'merchant/models/GrowthService';
import { makeEntityReducer } from 'merchant_common/reducers/entity';
import { set } from 'common/utils/immutable';

const FETCH_ANNOUNCEMENTS = 'FETCH_ANNOUNCEMENTS';

const updateAnnouncements = (status) => (state, action) => {
  switch (status) {
    case 'PENDING': {
      return set(state, 'announcements', {
        loading: true,
        announcements: [],
      });
    }
    case 'SUCCESS': {
      return set(state, 'announcements', {
        loading: false,
        announcements: action.payload,
      });
    }
    case 'ERROR': {
      return set(state, 'announcements', {
        loading: false,
        announcements: [],
      });
    }
    default: {
      return state;
    }
  }
};

export const fetchAnnouncements = (merchant_id, fromWhere = 'home') => {
  const growthService = new GrowthService();
  return {
    type: FETCH_ANNOUNCEMENTS,
    payload: growthService.getAnnouncements(merchant_id, fromWhere),
  };
};

const initialState = {
  announcements: {
    loading: false,
    announcements: [],
  },
};

export default makeEntityReducer(
  FETCH_ANNOUNCEMENTS,
  {
    [`${FETCH_ANNOUNCEMENTS}::PENDING`]: updateAnnouncements('PENDING'),
    [`${FETCH_ANNOUNCEMENTS}::SUCCESS`]: updateAnnouncements('SUCCESS'),
    [`${FETCH_ANNOUNCEMENTS}::ERROR`]: updateAnnouncements('ERROR'),
  },
  initialState,
);
