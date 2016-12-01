import ajax from 'merchant/utils/ajax'
import { fromJS } from 'immutable'

const ITEMS_FETCH = 'ITEMS_FETCH'
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

const getFixedINRAmount = (amount) => (amount/100).toFixed(2)

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    case `${ITEMS_FETCH}::PENDING`:
      return state.set('loading', true)

    case `${ITEMS_FETCH}::SUCCESS`:
      let itemsList = action.payload.data.items.map((item) => {
        item.amount_in_inr = getFixedINRAmount(item.amount)
        return item
      })

      return state.merge({
        loading: false,
        items: itemsList,
        count: action.payload.data.count
      })

    case `${ITEMS_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error
      })

    case `${ITEM_CREATE}::SUCCESS`:
      let newlyAddedItem = action.payload.data
      newlyAddedItem.amount_in_inr = getFixedINRAmount(newlyAddedItem.amount)
      return state.set('items', state.get('items').unshift(newlyAddedItem))

    case `${ITEM_EDIT}::SUCCESS`:
      let items = state.get('items')
      let updatedItem = action.payload.data
      updatedItem.amount_in_inr = getFixedINRAmount(updatedItem.amount)
      return state.set('items', items.update(
        items.findIndex((item) => item.get('id') === updatedItem.id),
        (item) => item.merge(updatedItem)
      ))

    default:
      return state
  }
}
