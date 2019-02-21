import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import SubscriptionsListFilter from 'merchant/components/Subscriptions/ListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import HeaderAction from 'rzp/ui/HeaderAction';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
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
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

const link = {
  title: 'Subscription Link',
  value: item => <CopyLink url={item.short_url} />,
};

@connect(state => state.subscriptions, { fetchAll })
export default class SubscriptionsListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Go To - Subscriptions',
    });
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
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
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <NavLink class="btn btn-primary" to="/subscriptions/new">
              <i class="i i-plus" />
              <span>Create New Subscription</span>
            </NavLink>
          </div>
        </HeaderAction>
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
            ...link,
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
