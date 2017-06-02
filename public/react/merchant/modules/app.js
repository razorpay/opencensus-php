import { set, merge } from 'rzp/utils/immutable';

const ROW_HIGHLIGHT_ADD = 'ROW_HIGHLIGHT_ADD';
const ROW_HIGHLIGHT_REMOVE = 'ROW_HIGHLIGHT_REMOVE';
const ROW_LUMINATE_ADD = 'ROW_LUMINATE_ADD';
const ROW_LUMINATE_REMOVE = 'ROW_LUMINATE_REMOVE';
const UPDATE_LOCATION = 'UPDATE_LOCATION';

export const updateLocation = payload => {
  return dispatch => {
    return dispatch({
      type: UPDATE_LOCATION,
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

export const removeActiveRow = id => {
  return dispatch => {
    return dispatch({
      type: ROW_HIGHLIGHT_REMOVE,
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
    case UPDATE_LOCATION:
      return merge(state, action.payload);

    case ROW_HIGHLIGHT_ADD:
      return set(state, 'activeRowId', action.payload.id);

    case ROW_HIGHLIGHT_REMOVE:
      return set(state, 'activeRowId', null);

    case ROW_LUMINATE_ADD:
      return set(state, 'luminateRowId', action.payload.id);

    case ROW_LUMINATE_REMOVE:
      return set(state, 'luminateRowId', null);

    default:
      return state;
  }
}
