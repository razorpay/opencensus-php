import { set } from 'rzp/utils/immutable';

const ROW_HIGHLIGHT_ADD = 'ROW_HIGHLIGHT_ADD';
const ROW_LUMINATE_ADD = 'ROW_LUMINATE_ADD';
const ROW_LUMINATE_REMOVE = 'ROW_LUMINATE_REMOVE';
const UPDATE_LOCATION = 'UPDATE_LOCATION';
const UPDATE_ENTITY = 'UPDATE_ENTITY';

export const setBaseLocation = location => {
  return {
    type: UPDATE_LOCATION,
    payload: location,
  };
};

export const setActiveEntity = id => {
  return {
    type: UPDATE_ENTITY,
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
    case UPDATE_ENTITY:
      return set(state, 'activeEntityId', action.payload);

    case UPDATE_LOCATION:
      return set(state, 'baseLocation', action.payload);

    case ROW_LUMINATE_ADD:
      return set(state, 'luminateRowId', action.payload.id);

    case ROW_LUMINATE_REMOVE:
      return set(state, 'luminateRowId', null);

    default:
      return state;
  }
}
