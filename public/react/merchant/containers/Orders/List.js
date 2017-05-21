import React, { Component } from 'react';
import { connect } from 'react-redux';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import Header from 'rzp/ui/Header';
import OrdersList from 'merchant/components/Orders/OrdersList';
import ListContainer from 'merchant/containers/ListContainer';
import OrdersListFilter from 'merchant/components/Orders/OrdersListFilter';
import { fetchOrders } from 'merchant/modules/orders/list';
import OrderDetails from 'merchant/containers/Orders/Details';
import { openSlider } from 'rzp/modules/slider';

@connect(state => state.orders, { fetchOrders, openSlider })
export default class OrdersListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchOrders(params);
  }

  showOrderDetails = order => {
    this.props.openSlider({
      component: <OrderDetails id={order.id} />,
      onOpenURL: `/app/orders/${order.id}`,
      onCloseURL: '/app/orders',
    });
  };

  render() {
    let { loading, orders, error } = this.props;

    return (
      <div class="content-wrapper">
        <OrdersListFilter
          form="orderListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        {error && <Alert type="error" message={error} />}

        <OrdersList
          orders={orders}
          isLoading={loading}
          onOrderClick={this.showOrderDetails}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={orders.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
