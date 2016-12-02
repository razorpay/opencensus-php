import Item from 'merchant/models/Item'
import { fromJS } from 'immutable'

const ITEMS_FETCH = 'ITEMS_FETCH'
const ITEM_CREATE = 'ITEM_CREATE'
const ITEM_EDIT = 'ITEM_EDIT'

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

let initialState = {
  loading: true,
  items: [],
  count: 0
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${ITEMS_FETCH}::PENDING`:
      return state.set('loading', true)

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

    default:
      return state
  }
}
