import { set, merge } from 'common/utils/immutable';
import Offer from 'merchant/models/Offer';
import { merchantFetch } from 'merchant/utils/ajax';

const OFFER_FETCH = 'OFFER_FETCH';
const OFFER_CREATE = 'OFFER_CREATE';
const OFFER_EDIT = 'OFFER_EDIT';
const OFFER_INIT = 'OFFER_INIT';

export const fetchOffer = (id) => {
  let offer = new Offer();

  return {
    type: OFFER_FETCH,
    payload: offer.fetch(id),
  };
};

export const fetchSubscriptionOffersUsage = (id) => {
  return merchantFetch(`offers/${id}/subscription/usage`);
};

export const saveOffer = (formData) => {
  const offer = new Offer(formData);
  return offer.save(formData, {
    headers: {
      'Content-Type': 'application/json',
    },
  });
};

let initialState = {
  loading: true,
  offer: {},
  error: null,
};

export default function (state = initialState, action) {
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
