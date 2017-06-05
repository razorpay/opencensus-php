import React, { Component } from 'react';
import { connect } from 'react-redux';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import OrdersList from 'merchant/components/Orders/OrdersList';
import ListContainer from 'merchant/containers/ListContainer';
import OrdersListFilter from 'merchant/components/Orders/OrdersListFilter';
import { fetchOrders as fetchAll } from 'rzp/modules/collection';

@connect(state => state.collection, { fetchAll })
export default class OrdersListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchAll(params);
  }

  render() {
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">
        <OrdersListFilter
          form="orderListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        {error && <Alert type="error" message={error} />}

        <OrdersList orders={items} isLoading={loading} />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={items.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
