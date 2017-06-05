import { set, merge } from 'rzp/utils/immutable';

const ROW_HIGHLIGHT_ADD = 'ROW_HIGHLIGHT_ADD';
const ROW_LUMINATE_ADD = 'ROW_LUMINATE_ADD';
const ROW_LUMINATE_REMOVE = 'ROW_LUMINATE_REMOVE';
const UPDATE_LOCATION = 'UPDATE_LOCATION';
const UPDATE_ENTITY = 'UPDATE_ENTITY';

export const updateLocation = payload => {
  return dispatch => {
    return dispatch({
      type: UPDATE_LOCATION,
      payload,
    });
  };
};

export const updateEntity = payload => {
  return dispatch => {
    return dispatch({
      type: UPDATE_ENTITY,
      payload,
    });
  };
};

export const setActiveRow = id => {
  return dispatch => {
    return dispatch({
      type: ROW_HIGHLIGHT_ADD,
      payload: { id },
    });
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
  activeRowId: null,
  luminateRowId: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case UPDATE_ENTITY:
      return set(state, 'activeEntityId', action.payload);

    case UPDATE_LOCATION:
      return set(state, 'baseLocation', action.payload);

    case ROW_HIGHLIGHT_ADD:
      return set(state, 'activeRowId', action.payload.id);

    case ROW_LUMINATE_ADD:
      return set(state, 'luminateRowId', action.payload.id);

    case ROW_LUMINATE_REMOVE:
      return set(state, 'luminateRowId', null);

    default:
      return state;
  }
}
