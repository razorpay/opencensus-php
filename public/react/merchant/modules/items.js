import Item from 'merchant/models/Item';
import { set, merge, unshift, remove } from 'rzp/utils/immutable';
import { makeCollectionReducer } from 'rzp/modules/collection';

const ITEMS = 'ITEMS';
const ITEMS_FETCH = 'ITEMS_FETCH';
const ITEMS_AUTOCOMPLETE_FETCH = 'ITEMS_AUTOCOMPLETE_FETCH';
const ITEM_CREATE = 'ITEM_CREATE';
const ITEM_EDIT = 'ITEM_EDIT';
const ITEM_DELETE = 'ITEM_DELETE';

export const fetchItems = params => {
  let item = new Item();

  return {
    type: ITEMS_FETCH,
    payload: item.fetchAll(params),
  };
};

export const fetchItemsForAutocomplete = () => {
  let item = new Item();

  return {
    type: ITEMS_AUTOCOMPLETE_FETCH,
    payload: item.fetchForAutocomplete(),
  };
};

export const saveItem = params => {
  let item = new Item(params);

  return {
    type: item.isNew ? ITEM_CREATE : ITEM_EDIT,
    payload: item.save(),
  };
};

export const deleteItem = params => {
  let item = new Item(params);

  return {
    type: ITEM_DELETE,
    payload: item.delete(),
    id: item.id,
  };
};

let initialState = {
  loading: true,
  items: [],
  count: 0,
};

const itemsCollectionReducer = makeCollectionReducer(ITEMS, initialState);

export default function(state = initialState, action) {
  switch (action.type) {
    case `${ITEMS_AUTOCOMPLETE_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${ITEMS_AUTOCOMPLETE_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        items: action.payload.data.items,
      });

    case `${ITEMS_AUTOCOMPLETE_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    default:
      return itemsCollectionReducer(state, action);
  }
}
