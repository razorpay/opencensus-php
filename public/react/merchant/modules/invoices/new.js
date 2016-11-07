import ajax from 'merchant/utils/ajax'
import customers from 'merchant/mocks/customers'
import { fromJS } from 'immutable'

export const fetchCustomers = () => {
  return (dispatch) => {
    return ajax('/customers').then((response) => {

    }).catch((err) => {
      return customers
    })
  }
}

let initialState = {
  loading: true,
  invoice: {}
}

export default function (state = fromJS(initialState), action) {
  switch(action.type) {
    default:
      return state
  }
}
