import BaseModel from './Base'
import ajax from 'merchant/utils/ajax'
import { getFixedINRAmount } from 'rzp/utils/rzp-utils'

export default class Order extends BaseModel {
  resourceUrl = '/orders'

  static fetchAll(data = {}) {
    return ajax('/orders', { data }).then((response) => {
      response.data.items = response.data.items.map((order) => new Order().deserialize(order))
      return response
    })
  }

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value)
    }
    return super.deserializeProperty(prop, value)
  }
}
