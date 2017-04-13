import GenericEntity from './GenericEntity'
import Refund from './Refund'
import { getFixedINRAmount } from 'rzp/utils/rzp-utils'
import ajax from 'merchant/utils/ajax'

export default class Payment extends GenericEntity {
  listRouteName = 'payment_fetch_multiple'
  detailsRouteName = 'payment_fetch_by_id'
  cardDetailsRoute = 'payment_fetch_card_details'
  fetchRefundsRoute = 'payment_fetch_refunds'

  fetchRefunds () {
    let data = {};
    const Klass = this.constructor
    data.url_params = JSON.stringify({
      '{id}': this.id
    })
    data.route_name = this.fetchRefundsRoute
    return this.makeGenericAjaxCall({ data }).then((response) => {
      response.data.items = response.data.items.map((item) =>
        new Refund().deserialize(item)
      )
      return response
    })
  }

  fetchCardDetails () {
    let data = {};
    const Klass = this.constructor
    data.url_params = JSON.stringify({
      '{id}': this.id
    })
    data.route_name = this.cardDetailsRoute
    return this.makeGenericAjaxCall({ data }).then((response) => {
      return response
    })
  }
}
