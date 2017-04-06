import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'
import OrderDetails from 'merchant/components/Orders/OrderDetails'
import * as OrderActions from 'merchant/modules/orders/details'

@connect(
  (state) => state.order,
  OrderActions
)
export default class OrderDetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchOrder(this.props.id)
  }

  fetchOrderPayments = (order) => {
    return this.props.fetchOrderPayments(order)
  }

  render() {
    let {
      loading,
      error,
      order,
      payments,
    } = this.props
    let statusMsg = {}

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error
      }
    }

    return (
      <div class='react-root'>
        <Header title='Order Detail' />

        <div class='content-wrapper'>
          <OrderDetails
            order={order}
            payments={payments}
            onTogglePayments={this.fetchOrderPayments}
            isLoading={loading}
            statusMsg={statusMsg}
          />
        </div>
      </div>
    )
  }
}
