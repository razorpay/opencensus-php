import {
  makeActionCollectionReducer,
  listFetchPendingState,
  listFetchSuccessState,
  listFetchErrorState,
  appendEntityToList,
  updateEntityInList,
} from 'merchant/reducers/collection';
import Offer from 'merchant/models/Offer';

export const OFFERS_FETCH = 'OFFERS_FETCH';
export const OFFER_FETCH = 'OFFER_FETCH';
export const OFFERS_AUTOCOMPLETE_FETCH = 'OFFERS_AUTOCOMPLETE_FETCH';
export const OFFER_APPEND = 'OFFER_APPEND';
export const OFFER_UPDATE = 'OFFER_UPDATE';

export const fetchOffers = (params) => {
  let offer = new Offer();

  return {
    type: OFFERS_FETCH,
    payload: offer.fetchAll(params),
  };
};

export const fetchOffer = (id) => {
  let offer = new Offer();

  return {
    type: OFFER_FETCH,
    payload: offer.fetch(id),
  };
};

/* Hook to update newly-created/edited payment link in redux list*/
export const appendOfferInReduxList = (offer) => {
  const newOffer = new Offer(offer);
  return {
    type: OFFER_APPEND,
    payload: newOffer,
  };
};

export const updateOfferInReduxList = (offer) => {
  return {
    type: OFFER_UPDATE,
    payload: offer,
  };
};

export default makeActionCollectionReducer('OFFERS', {
  [`${OFFERS_AUTOCOMPLETE_FETCH}::PENDING`]: listFetchPendingState,
  [`${OFFERS_AUTOCOMPLETE_FETCH}::SUCCESS`]: listFetchSuccessState,
  [`${OFFERS_AUTOCOMPLETE_FETCH}::ERROR`]: listFetchErrorState,
  [`${OFFERS_AUTOCOMPLETE_FETCH}::ERROR`]: listFetchErrorState,
  [OFFER_APPEND]: appendEntityToList,
  [OFFER_UPDATE]: updateEntityInList,
});
