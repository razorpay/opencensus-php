import { set } from 'rzp/utils/immutable';

const ROW_LUMINATE_ADD = 'ROW_LUMINATE_ADD';
const ROW_LUMINATE_REMOVE = 'ROW_LUMINATE_REMOVE';
const LOCATION_UPDATE = 'LOCATION_UPDATE';
const ENTITY_UPDATE = 'ENTITY_UPDATE';
const SEC_ENTITY_UPDATE = 'SEC_ENTITY_UPDATE';

export const setBaseLocation = location => {
  return {
    type: LOCATION_UPDATE,
    payload: location,
  };
};

export const setActiveEntity = id => {
  return {
    type: ENTITY_UPDATE,
    payload: id,
  };
};

// Usage: If dual view slider is opened then 2 rows will be highlighted in the scene as per activeEntityId and activeSecEntityId
export const setSecActiveEntity = id => {
  return {
    type: SEC_ENTITY_UPDATE,
    payload: id,
  };
};

export const luminateRow = id => {
  return dispatch => {
    dispatch({
      type: ROW_LUMINATE_ADD,
      payload: { id },
    });

    setTimeout(() => {
      dispatch({
        type: ROW_LUMINATE_REMOVE,
      });
    }, 6000);
  };
};

let initialState = {
  luminateRowId: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case ENTITY_UPDATE:
      return set(state, 'activeEntityId', action.payload);

    case SEC_ENTITY_UPDATE:
      return set(state, 'activeSecEntityId', action.payload);

    case LOCATION_UPDATE:
      return set(state, 'baseLocation', action.payload);

    case ROW_LUMINATE_ADD:
      return set(state, 'luminateRowId', action.payload.id);

    case ROW_LUMINATE_REMOVE:
      return set(state, 'luminateRowId', null);

    default:
      return state;
  }
}
