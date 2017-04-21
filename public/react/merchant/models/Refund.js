import GenericEntity from './GenericEntity'
import { getFixedINRAmount } from 'rzp/utils/rzp-utils'
import ajax from 'merchant/utils/ajax'

export default class Refund extends GenericEntity {
  listRouteName = 'refund_fetch'
  detailsRouteName = 'refund_fetch_by_id'

  deserializeProperty(prop, value) {
    if (prop === 'amount') {
      this.amountInINR = getFixedINRAmount(value)
    }
    return super.deserializeProperty(prop, value)
  }
}
