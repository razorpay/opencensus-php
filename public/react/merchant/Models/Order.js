import Entity from './Entity'
import { getFixedINRAmount } from 'rzp/utils/rzp-utils'
import Payment from './Payment'
import ajax from 'merchant/utils/ajax'

export default class Order extends Entity {
  static resourceUrl = '/orders'

  fetchPayments() {
    return ajax({
      url: `${this.getResourceUrl()}/payments`
    }).then((response) => {
      response.data.items = response.data.map((item) => new Payment().deserialize(item))
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
