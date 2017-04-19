import GenericEntity from './GenericEntity'
import Refund from './Refund'
import { getFixedINRAmount } from 'rzp/utils/rzp-utils'
import ajax from 'merchant/utils/ajax'
import { fetchPayment } from 'merchant/modules/payments/details'

export default class Payment extends GenericEntity {
  listRouteName = 'payment_fetch_multiple'
  detailsRouteName = 'payment_fetch_by_id'

  fetchRefunds () {
    let data = {}
    data.url_params = JSON.stringify({
      '{id}': this.id
    })
    data.route_name = 'payment_fetch_refunds'
    return this.makeGenericAjaxCall({ data }).then((response) => {
      response.data.items = response.data.items.map((item) =>
        new Refund().deserialize(item)
      )
      return response
    })
  }

  capture () {
    let data = {}
    const method = 'post'
    const Klass = this.constructor

    data.body = {
      amount: this.amount,
      currency: this.currency
    }

    data.url_params = JSON.stringify({
      '{id}': this.id
    })

    data.route_name = 'payment_capture'
    return this.makeGenericAjaxCall({ method, data }).then((response) => {
      return new Klass(response.data)
    })
  }

  refund (params) {
    let data = {}
    const method = 'post'
    const Klass = this.constructor

    data.body = {
      amount: params.amount,
      notes: {
        comment: params.comment
      }
    }

    data.url_params = JSON.stringify({
      '{id}': this.id
    })

    data.route_name = 'payment_refund'
    return this.makeGenericAjaxCall({ method, data })
  }

  fetchCardDetails () {
    let data = {}
    const Klass = this.constructor
    data.url_params = JSON.stringify({
      '{id}': this.id
    })
    data.route_name = 'payment_fetch_card_details'
    return this.makeGenericAjaxCall({ data })
  }
}
