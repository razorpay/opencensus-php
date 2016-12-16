import Item from 'merchant/models/Item'
import { fromJS } from 'immutable'

const ITEMS_FETCH = 'ITEMS_FETCH'
const ITEM_CREATE = 'ITEM_CREATE'
const ITEM_EDIT = 'ITEM_EDIT'
const ITEM_DELETE = 'ITEM_DELETE'
const HIGHLIGHT_ITEM = 'HIGHLIGHT_ITEM'
const REMOVE_ITEM_HIGHLIGHT = 'REMOVE_ITEM_HIGHLIGHT'

export const fetchItems = () => {
  return (dispatch) => {
    return dispatch({
      type: ITEMS_FETCH,
      payload: Item.fetchAll()
    })
  }
}

export const saveItem = (params) => {
  return (dispatch) => {
    return dispatch({
      type: params.id ? ITEM_EDIT : ITEM_CREATE,
      payload: new Item(params).save()
    })
  }
}

export const deleteItem = (params) => {
  return (dispatch) => {
    return new Item(params).delete().then(() => {
      dispatch({
        type: ITEM_DELETE,
        payload: params
      })
    })
  }
}

export const highlightItemRow = (item) => {
  return (dispatch) => {
    dispatch({
      type: HIGHLIGHT_ITEM,
      payload: item
    })

    setTimeout(() => {
      dispatch({
        type: REMOVE_ITEM_HIGHLIGHT
      })
    }, 5000)
  }
}


let initialState = {
  loading: true,
  items: [],
  count: 0,
  highlightRowId: null
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${ITEMS_FETCH}::PENDING`:
      return state.merge({
        loading: true,
        highlightRowId: null
      })

    case `${ITEMS_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        items: action.payload.data.items,
        count: action.payload.data.count
      })

    case `${ITEMS_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error
      })

    case `${ITEM_CREATE}::SUCCESS`:
      return state.set('items', state.get('items').unshift(action.payload))

    case `${ITEM_EDIT}::SUCCESS`:
      let items = state.get('items')
      let updatedItem = action.payload
      return state.set('items', items.update(
        items.findIndex((item) => item.get('id') === updatedItem.id),
        (item) => item.merge(updatedItem)
      ))

    case `${ITEM_DELETE}::SUCCESS`:
      return state.set('items', state.get('items').remove(action.payload))

    case HIGHLIGHT_ITEM:
      return state.set('highlightRowId', action.payload.get('id'))

    case REMOVE_ITEM_HIGHLIGHT:
      return state.set('highlightRowId', null)

    default:
      return state
  }
}
