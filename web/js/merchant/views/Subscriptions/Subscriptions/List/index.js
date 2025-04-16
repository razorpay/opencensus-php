/* eslint-disable */
import React from 'react';
import { connect } from 'react-redux';
import { change } from 'redux-form';
import { NavLink } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { RZPFeatures } from 'merchant/helpers/data';
import DataTable from 'common/ui/Table/DataTable';
import HeaderAction from 'common/ui/HeaderAction';
import { fetchSubscriptions as fetchAll } from 'merchant/reducers/subscriptions';
import DocsLink from 'merchant/components/DocsLink';
import EmptyList from 'merchant/components/EmptyList';
import CopyLink from 'merchant/components/CopyLink';
import SubscriptionsListFilter from 'merchant/views/Subscriptions/Subscriptions/components/ListFilter';
import ListContainer from 'merchant/containers/ListContainer';
import {
  subscriptionId,
  planId,
  customerId,
  nextDueOn,
  createdAt,
  status,
} from 'common/ui/item/pair';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import ExpirySubscriptions from './ExpirySubscriptions';
import analytics from 'merchant/views/Subscriptions/analytics';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

const link = {
  title: 'Subscription Link',
  value: (item) => <CopyLink url={item.short_url} />,
};

class SubscriptionsListContainer extends ListContainer {
  filterEle = React.createRef();

  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Go To - Subscriptions',
    });
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Subscriptions',
        eventAction: 'Search - Subscriptions',
        eventLabel: label,
      });
      analytics.track(`subscription.search.${label}`);
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Clear Search Params - Subscriptions',
    });
    analytics.track('subscription.search.clear');
    this.onFilterChange();
  };

  onFilterChange = () => {
    if (this.quickFilterEle && this.quickFilterEle.state.selectedQuickFilter) {
      this.quickFilterEle.setState({ selectedQuickFilter: null });
    }
  };

  onClickPaginate = (params, type) => {
    analytics.track(`subscription.browse.${type}`);

    this.paginate(params);
  };

  render() {
    const { user } = this.props;
    return (
      <div className="content-wrapper">
        <HeaderAction responsive>
          <div className="btn-toolbar pull-right">
            <TakeATourButton
              feature={RZPFeatures.SUBSCRIPTIONS}
              onClick={() => analytics.track('subscription.search.help')}
            />

            <DocsLink
              url="https://razorpay.com/docs/subscriptions/"
              onClick={() => {
                analytics.track('subscription.search.documentation');
              }}
            />
            <span className="cta-container">
              <NavLink
                className="btn btn-primary"
                to="/subscriptions/new"
                onClick={() => {
                  selfServeTrackInitiate({
                    selfServeAction: 'Subscription Created',
                    page: 'Subscriptions',
                    screen: 'Subscriptions',
                  });
                  analytics.track('subscription.create.initiate');
                }}
              >
                <i className="i i-plus" />
                <span>Create New Subscription</span>
              </NavLink>
            </span>
          </div>
        </HeaderAction>

        <ExpirySubscriptions
          location={this.props.location}
          history={this.props.history}
          selectedQuickFilter={this.selectedQuickFilter}
          changeFilterField={this.props.changeListFilterField}
          ref={(filter) => (this.quickFilterEle = filter)}
        />

        <SubscriptionsListFilter
          form="subscriptionsListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onFieldChange={this.onFilterChange}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Subscriptions"
          columns={[subscriptionId, planId, ...link, customerId, nextDueOn, createdAt, status]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.onClickPaginate}
          EmptyComponent={EmptyComponent}
          {...this.props}
        />
      </div>
    );
  }
}

function EmptyComponent() {
  return (
    <EmptyList
      description={
        <>
          <div>There are no subscriptions yet!!</div>
          <div>Create a plan first to create a subscription.</div>
        </>
      }
    />
  );
}

export default connect(
  (state) => ({
    ...state.subscriptions,
    user: state.session.user,
  }),
  {
    fetchAll,
    changeListFilterField: (field, value) => (dispatch) => {
      dispatch(change('subscriptionsListFilter', field, value));
    },
  },
)(withRouter(SubscriptionsListContainer));
