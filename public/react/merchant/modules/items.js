import Item from 'merchant/models/Item';
import {
  makeActionCollectionReducer,
  listFetchPendingState,
  listFetchSuccessState,
  listFetchErrorState,
} from 'rzp/modules/collection';

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

export const fetchItemsForAutocomplete = data => {
  let item = new Item();

  return {
    type: ITEMS_AUTOCOMPLETE_FETCH,
    payload: item.fetchForAutocomplete(data),
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

export default makeActionCollectionReducer('ITEMS', {
  [`${ITEMS_AUTOCOMPLETE_FETCH}::PENDING`]: listFetchPendingState,
  [`${ITEMS_AUTOCOMPLETE_FETCH}::SUCCESS`]: listFetchSuccessState,
  [`${ITEMS_AUTOCOMPLETE_FETCH}::ERROR`]: listFetchErrorState,
});
