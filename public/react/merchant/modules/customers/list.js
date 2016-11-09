import ajax from 'merchant/utils/ajax'
import customers from 'merchant/mocks/customers'

export const fetchCustomers = () => {
  return (dispatch) => {
    return ajax('/customers').then((response) => {
      return response.data.items
    })
  }
}
