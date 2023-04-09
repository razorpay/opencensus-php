import { set, merge } from 'common/utils/immutable';
import Offer from 'merchant/models/Offer';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateOfferDataFormat } from 'merchant/views/Offers/utils';

const OFFER_FETCH = 'OFFER_FETCH';
const OFFER_CREATE = 'OFFER_CREATE';
const OFFER_EDIT = 'OFFER_EDIT';
const OFFER_INIT = 'OFFER_INIT';

export const fetchOffer = (id) => {
  const offer = new Offer();

  return {
    type: OFFER_FETCH,
    payload: offer.fetch(id),
  };
};

export const fetchSubscriptionOffersUsage = (id) => {
  return merchantFetch(`offers/${id}/subscription/usage`);
};

export const saveOffer = (formData) => {
  // Update the offer data format to incorporate backend changes
  const updatedOfferData = updateOfferDataFormat(formData);

  const offer = new Offer(updatedOfferData);
  return offer.save(updatedOfferData, {
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

const initialState = {
  loading: true,
  offer: {},
  error: null,
};

export default function offerDetailsReducer(state = initialState, action) {
  switch (action.type) {
    case `${OFFER_FETCH}::PENDING`:
      return set(state, 'loading', true);
    case `${OFFER_FETCH}::SUCCESS`:
    case `${OFFER_CREATE}::SUCCESS`:
    case `${OFFER_EDIT}::SUCCESS`:
    case OFFER_INIT:
      return merge(state, {
        loading: false,
        offer: action.payload,
        error: null,
      });

    case `${OFFER_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        offer: initialState.invoice,
      });

    default:
      return state;
  }
}
