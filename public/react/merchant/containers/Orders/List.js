import React, { Component } from 'react'
import { connect } from 'react-redux'
import Pager from 'rzp/ui/Pager'
import Alert from 'rzp/ui/Forms/Alert'
import OrdersList from 'merchant/components/Orders/OrdersList'
import ListContainer from 'merchant/containers/ListContainer'
import OrdersListFilter from 'merchant/components/Orders/OrdersListFilter'
import { fetchOrders } from 'merchant/modules/orders/list'

@connect(
  (state) => state.orders,
  { fetchOrders }
)
export default class OrdersListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchOrders(params)
  }

  render() {
    let { loading, orders } = this.props

    return (
      <div class='react-root'>
        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <div class='panel-heading'>
              Orders List
            </div>

            <div class='panel-body'>
              <OrdersListFilter
                form='orderListFilter'
                count={this.state.count}
                onSubmit={this.search}
              />
            </div>

            <Alert
              type={status.type}
              message={status.message}
            />

            <OrdersList
              orders={orders}
              isLoading={loading}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={orders.length}
              onClick={this.fetchAll}
            />
          </div>
        </div>
      </div>
    )
  }
}
