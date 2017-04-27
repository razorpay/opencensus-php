import Item from 'merchant/models/Item';
import { set, merge, unshift, remove } from 'rzp/utils/immutable';

const ITEMS_FETCH = 'ITEMS_FETCH';
const ITEMS_AUTOCOMPLETE_FETCH = 'ITEMS_AUTOCOMPLETE_FETCH';
const ITEM_CREATE = 'ITEM_CREATE';
const ITEM_EDIT = 'ITEM_EDIT';
const ITEM_DELETED = 'ITEM_DELETED';
const HIGHLIGHT_ITEM = 'HIGHLIGHT_ITEM';
const REMOVE_ITEM_HIGHLIGHT = 'REMOVE_ITEM_HIGHLIGHT';

export const fetchItems = params => {
  return dispatch => {
    let item = new Item();
    return dispatch({
      type: ITEMS_FETCH,
      payload: item.fetchAll(params),
    });
  };
};

export const fetchItemsForAutocomplete = () => {
  return dispatch => {
    let item = new Item();
    return dispatch({
      type: ITEMS_AUTOCOMPLETE_FETCH,
      payload: item.fetchForAutocomplete(),
    });
  };
};

export const saveItem = params => {
  return dispatch => {
    let item = new Item(params);
    return dispatch({
      type: item.isNew ? ITEM_CREATE : ITEM_EDIT,
      payload: item.save(),
    });
  };
};

export const deleteItem = params => {
  return dispatch => {
    let item = new Item(params);
    return item.delete().then(() => {
      dispatch({
        type: ITEM_DELETED,
        payload: item,
      });
    });
  };
};

export const highlightItemRow = params => {
  return dispatch => {
    dispatch({
      type: HIGHLIGHT_ITEM,
      payload: params,
    });

    setTimeout(() => {
      dispatch({
        type: REMOVE_ITEM_HIGHLIGHT,
      });
    }, 6000);
  };
};

let initialState = {
  loading: true,
  items: [],
  count: 0,
  highlightRowId: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${ITEMS_FETCH}::PENDING`:
    case `${ITEMS_AUTOCOMPLETE_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        highlightRowId: null,
      });

    case `${ITEMS_FETCH}::SUCCESS`:
    case `${ITEMS_AUTOCOMPLETE_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        items: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${ITEMS_FETCH}::ERROR`:
    case `${ITEMS_AUTOCOMPLETE_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    case `${ITEM_CREATE}::SUCCESS`:
      return set(state, 'items', unshift(state.items, action.payload));

    case `${ITEM_EDIT}::SUCCESS`:
      let itemIndex = state.items.findIndex(
        item => item.id === action.payload.id
      );
      return set(state, `items.${itemIndex}`, action.payload);

    case ITEM_DELETED:
      var itemsList = remove(
        state.items,
        item => item.id === action.payload.id
      );
      return set(state, 'items', itemsList);

    case HIGHLIGHT_ITEM:
      return set(state, 'highlightRowId', action.payload.id);

    case REMOVE_ITEM_HIGHLIGHT:
      return set(state, 'highlightRowId', null);

    default:
      return state;
  }
}
