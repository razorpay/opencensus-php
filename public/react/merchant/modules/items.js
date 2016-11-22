import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const ITEMS_FETCH = 'ITEMS_FETCH'
const ITEMS_ADDED = 'ITEMS_ADDED'
const ITEMS_EDITED = 'ITEMS_EDITED'

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
    return ajax({
      url: '/item',
      method: 'post',
      data
    })
  }
}

export const editItem = (id, data) => {
  return (dispatch) => {
    return ajax({
      url: `/item/${id}`,
      method: 'put',
      data
    })
  }
}


export const itemAdded = (item) => {
  return {
    type: ITEMS_ADDED,
    payload: item
  }
}

export const itemEdited = (item) => {
  return {
    type: ITEMS_EDITED,
    payload: item
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

    case ITEMS_ADDED:
      return state.set('items', state.get('items').unshift(action.payload))

    case ITEMS_EDITED:
      let items = state.get('items')
      return state.set('items', items.update(
        items.findIndex((item) => item.get('id') === action.payload.id),
        (item) => item.merge(action.payload)
      ))

    default:
      return state
  }
}
