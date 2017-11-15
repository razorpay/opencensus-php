import { Component } from 'react';
import { connect } from 'react-redux';
import SubscriptionsListFilter from 'merchant/components/Subscriptions/ListFilter';
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
import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

@connect(state => state.subscriptions, { fetchAll })
export default class SubscriptionsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Go To - Subscriptions',
    });
  }

  onSearchAnalytics = params => {
    const label = stringifyQueryParamsWithPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Subscriptions',
        eventAction: 'Search - Subscriptions',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Clear Search Params - Subscriptions',
    });
  };

  render() {
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">
        <SubscriptionsListFilter
          form="subscriptionsListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
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
