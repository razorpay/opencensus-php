import { Component } from 'react';
import { connect } from 'react-redux';
import SubscriptionsListFilter
  from 'merchant/components/Subscriptions/ListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchSubscriptions as fetchAll } from 'merchant/modules/subscriptions';
import {
  subscriptionId,
  planId,
  customerId,
  nextDueOn,
  createdAt,
  status,
} from 'rzp/ui/item/pair';

@connect(state => state.subscriptions, { fetchAll })
export default class SubscriptionsListContainer extends ListContainer {
  render() {
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">
        <SubscriptionsListFilter
          form="subscriptionsListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />

        <DataTable
          title="Subscriptions"
          columns={[
            subscriptionId,
            planId,
            customerId,
            nextDueOn,
            createdAt,
            status,
          ]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          {...this.props}
        />
      </div>
    );
  }
}
