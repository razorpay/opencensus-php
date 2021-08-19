import { set, merge } from 'common/utils/immutable';

import Dispute from 'merchant/models/Dispute';
import { merchantFetch } from 'merchant/utils/ajax';

export const DISPUTES_LOAD = 'DISPUTES_LOAD';
export const FETCH_OPEN = 'FETCH_OPEN_DISPUTES';
export const DISPUTE_FETCH = 'DISPUTE_FETCH';
export const ACCEPT_DISPUTE = 'ACCEPT_DISPUTE';
export const CONTEST_DISPUTE = 'CONTEST_DISPUTE';
export const FETCH_FILE_TYPES = 'FETCH_FILE_TYPES';

export const loadDispute = (payload) => {
  let dispute = new Dispute();
  return {
    type: DISPUTE_FETCH,
    payload: dispute.fetch(
      payload.id ? payload.id : payload,
      {},
      { expand: ['transaction.settlement'] },
    ),
  };
};

export const fetchOpen = () => {
  const dispute = new Dispute();
  return {
    type: FETCH_OPEN,
    payload: dispute.fetchOpen(),
  };
};

export const accept = (disputeId) => {
  return {
    type: ACCEPT_DISPUTE,
    payload: merchantFetch({
      url: `disputes/${disputeId}/accept`,
      method: 'POST',
    }),
  };
};

export const contest = (disputeId, data) => {
  return {
    type: CONTEST_DISPUTE,
    payload: merchantFetch({
      url: `disputes/${disputeId}/contest`,
      method: 'PATCH',
      data,
    }),
  };
};

export const fetchFileTypes = () => {
  return {
    type: FETCH_FILE_TYPES,
    payload: merchantFetch({
      url: `disputes/documents/types`,
      method: 'GET',
    }),
  };
};

const initialState = {
  loading: true,
  item: {},
  error: null,
  openDisputes: 0,
  fileTypes: [],
  fileTypesMap: {},
};

export default function (state = initialState, action) {
  switch (action.type) {
    case DISPUTES_LOAD:
      return merge(state, {
        loading: false,
        item: action.payload,
      });

    case `${DISPUTE_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${DISPUTE_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        item: action.payload,
      });

    case `${DISPUTE_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors[0],
      });

    case `${FETCH_OPEN}::SUCCESS`:
      return merge(state, {
        openDisputes: action.payload,
      });

    case `${ACCEPT_DISPUTE}::SUCCESS`:
      return merge(state, {
        loading: false,
        item: action.payload.data,
      });

    case `${CONTEST_DISPUTE}::SUCCESS`:
      return merge(state, {
        loading: false,
        item: action.payload.data,
      });
    case `${FETCH_FILE_TYPES}::SUCCESS`:
      const fileTypesMap = {};
      action.payload.data.forEach((type) => {
        fileTypesMap[type.name] = type;
      });
      return merge(state, {
        fileTypes: action.payload.data,
        fileTypesMap,
      });

    default:
      return state;
  }
}
