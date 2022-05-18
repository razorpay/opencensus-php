import GrowthService from 'merchant/models/GrowthService/GrowthService';
import { makeEntityReducer } from 'merchant_common/reducers/entity';
import { set } from 'common/utils/immutable';
import { assetNames } from 'merchant/models/GrowthService/data';

const FETCH_ANNOUNCEMENTS = 'FETCH_ANNOUNCEMENTS';
const FETCH_BANNERS = 'FETCH_BANNERS';
const FETCH_EXCLUSIVE_OFFER = 'FETCH_EXCLUSIVE_OFFER';
const FETCH_BANNERS_CAROUSEL = 'FETCH_BANNERS_CAROUSEL';
const FETCH_GS_MODAL = 'FETCH_GS_MODAL';

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
const updateExclusiveOffer = (status) => updateAssets(status, assetNames.EXCLUSIVE_OFFER);
const updateCarouselBanners = (status) => updateAssets(status, assetNames.BANNER_CAROUSEL_ITEM);
const updateGSModal = (status) => updateAssets(status, assetNames.MODAL);

const initialState = {
  announcements: {
    loading: false,
    announcements: [],
  },
  banners: {
    loading: false,
    banners: [],
  },
  exclusive_offers: {
    loading: true,
    exclusive_offers: {},
  },
  banner_carousel_items: {
    loading: false,
    banner_carousel_items: [],
  },
  gs_modals: {
    loading: true,
    gs_modals: {},
  },
};

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
export const fetchCarouselBanner = ({ fromWhere = 'home' }) => {
  const growthService = new GrowthService();
  return {
    type: FETCH_BANNERS_CAROUSEL,
    payload: growthService.getCarouselBanners(fromWhere),
  };
};

export const fetchExclusiveOffer = ({ fromWhere = 'home' }) => {
  const growthService = new GrowthService();
  const payload = growthService.getExclusiveOfferModal(fromWhere);
  return {
    type: FETCH_EXCLUSIVE_OFFER,
    payload,
  };
};

export const fetchGSModal = ({ template_id }) => {
  const growthService = new GrowthService();
  const payload = growthService.getGSModal(template_id);
  initialState.gs_modals.loading = false;
  return {
    type: FETCH_GS_MODAL,
    payload,
  };
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
    [`${FETCH_EXCLUSIVE_OFFER}::PENDING`]: updateExclusiveOffer('PENDING'),
    [`${FETCH_EXCLUSIVE_OFFER}::SUCCESS`]: updateExclusiveOffer('SUCCESS'),
    [`${FETCH_EXCLUSIVE_OFFER}::ERROR`]: updateExclusiveOffer('ERROR'),
    [`${FETCH_BANNERS_CAROUSEL}::PENDING`]: updateCarouselBanners('PENDING'),
    [`${FETCH_BANNERS_CAROUSEL}::SUCCESS`]: updateCarouselBanners('SUCCESS'),
    [`${FETCH_BANNERS_CAROUSEL}::ERROR`]: updateCarouselBanners('ERROR'),
    [`${FETCH_GS_MODAL}::PENDING`]: updateGSModal('PENDING'),
    [`${FETCH_GS_MODAL}::SUCCESS`]: updateGSModal('SUCCESS'),
    [`${FETCH_GS_MODAL}::ERROR`]: updateGSModal('ERROR'),
  },
  initialState,
);
