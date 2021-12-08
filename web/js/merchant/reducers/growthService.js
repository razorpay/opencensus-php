import GrowthService from 'merchant/models/GrowthService/GrowthService';
import { makeEntityReducer } from 'merchant_common/reducers/entity';
import { set } from 'common/utils/immutable';
import { assetNames } from 'merchant/models/GrowthService/data';

const FETCH_ANNOUNCEMENTS = 'FETCH_ANNOUNCEMENTS';
const FETCH_BANNERS = 'FETCH_BANNERS';

const updateAssets = (status, assetName) => (state, action) => {
  const key = `${assetName.toLowerCase()}s`;

  switch (status) {
    case 'PENDING': {
      return set(state, `${key}`, {
        loading: true,
        [key]: [],
      });
    }
    case 'SUCCESS': {
      return set(state, `${key}`, {
        loading: false,
        [key]: action.payload,
      });
    }
    case 'ERROR': {
      return set(state, `${key}`, {
        loading: false,
        [key]: [],
      });
    }
    default: {
      return state;
    }
  }
};

const updateAnnouncements = (status) => updateAssets(status, assetNames.ANNOUNCEMENT);
const updateBanners = (status) => updateAssets(status, assetNames.BANNER);

export const fetchAnnouncements = ({ fromWhere = 'home' }) => {
  const growthService = new GrowthService();
  return {
    type: FETCH_ANNOUNCEMENTS,
    payload: growthService.getAnnouncements(fromWhere),
  };
};

export const fetchBanners = ({ fromWhere = 'home' }) => {
  const growthService = new GrowthService();
  return {
    type: FETCH_BANNERS,
    payload: growthService.getBanners(fromWhere),
  };
};

const initialState = {
  announcements: {
    loading: false,
    announcements: [],
  },
  banners: {
    loading: false,
    banners: [],
  },
};

export default makeEntityReducer(
  FETCH_ANNOUNCEMENTS,
  {
    [`${FETCH_ANNOUNCEMENTS}::PENDING`]: updateAnnouncements('PENDING'),
    [`${FETCH_ANNOUNCEMENTS}::SUCCESS`]: updateAnnouncements('SUCCESS'),
    [`${FETCH_ANNOUNCEMENTS}::ERROR`]: updateAnnouncements('ERROR'),
    [`${FETCH_BANNERS}::PENDING`]: updateBanners('PENDING'),
    [`${FETCH_BANNERS}::SUCCESS`]: updateBanners('SUCCESS'),
    [`${FETCH_BANNERS}::ERROR`]: updateBanners('ERROR'),
  },
  initialState,
);
