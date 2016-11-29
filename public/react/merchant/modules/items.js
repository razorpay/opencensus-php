import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const ITEMS_FETCH = 'ITEMS_FETCH'
const ITEMS_ADDED = 'ITEMS_ADDED'
const ITEMS_EDITED = 'ITEMS_EDITED'
const ITEM_CREATE = 'ITEM_CREATE'
const ITEM_EDIT = 'ITEM_EDIT'

export const fetchItems = () => {
  return (dispatch) => {
    return dispatch({
      type: ITEMS_FETCH,
      payload: ajax('/items')
    })
  }
}

export const createItem = (data) => {
  return (dispatch) => {
    return dispatch({
      type: ITEM_CREATE,
      payload: ajax({
        url: '/item',
        method: 'post',
        data
      })
    })
  }
}

export const editItem = (id, data) => {
  return (dispatch) => {
    return dispatch({
      type: ITEM_EDIT,
      payload: ajax({
        url: `/item/${id}`,
        method: 'put',
        data
      })
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
      return state.set('items', state.get('items').unshift(action.payload.data))

    case `${ITEM_EDIT}::SUCCESS`:
      let items = state.get('items')
      return state.set('items', items.update(
        items.findIndex((item) => item.get('id') === action.payload.data.id),
        (item) => item.merge(action.payload)
      ))

    default:
      return state
  }
}
