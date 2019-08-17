import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import {
  planId,
  planName,
  planAmount,
  planBillingCycle,
  createdAt,
} from 'rzp/ui/item/pair';
import HeaderAction from 'rzp/ui/HeaderAction';
import DataTable from 'rzp/ui/Table/DataTable';

import { fetchSubscriptions } from 'merchant/modules/subscriptions';
import { fetchPlans as fetchAll } from 'merchant/modules/plans';
import * as ModalActions from 'rzp/modules/modals';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import EmptyList from 'merchant/components/EmptyList';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';

import { getSubscriptionQuickGuideIsClosed } from 'merchant/containers/Subscriptions/QuickGuide';

import PlansListFilter from 'merchant/components/Plans/ListFilter';
import ListContainer from 'merchant/containers/ListContainer';

import DataTable from 'rzp/ui/Table/DataTable';
import HeaderAction from 'rzp/ui/HeaderAction';

import { fetchPlans as fetchAll } from 'merchant/modules/plans';
import * as ModalActions from 'rzp/modules/modals';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import EmptyList from 'merchant/components/EmptyList';
import PlansListFilter from 'merchant/components/Plans/ListFilter';

import ListContainer from 'merchant/containers/ListContainer';

@connect(state => state.plans, { fetchAll, ...ModalActions })
export default class PlansListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Go To - Plans',
    });
  }

  onSearchAnalytics = params => {
    const label = getKeysSeparatedByPipe(params);
    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Subscriptions',
        eventAction: 'Search - Plans',
        eventLabel: label,
      });
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Clear Search Params - Plans',
    });
  };

  render() {
    let { docUrl } = this.props;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            {docUrl && <DocsLink url={docUrl} />}
            <ShowWhen
              additionalCondition={user => user.isAllowedEdit('subscriptions')}
            >
              <NavLink to="/plans/new">
                <button class="pull-right btn btn-primary">
                  <i class="i i-plus" />
                  <span>New Plan</span>
                </button>
              </NavLink>
            </ShowWhen>
          </div>
        </HeaderAction>

        <PlansListFilter
          form="plansListFilter"
          count={this.state.count}
          onSubmit={this.search}
          onSearchAnalytics={this.onSearchAnalytics}
          onClearAnalytics={this.onClearAnalytics}
        />

        <DataTable
          title="Plans"
          columns={[planId, planName, planAmount, planBillingCycle, createdAt]}
          count={this.state.count}
          skip={this.state.skip}
          paginate={this.paginate}
          EmptyComponent={EmptyComponent}
          {...this.props}
        />
      </div>
    );
  }
}

const EmptyComponent = () => (
  <EmptyList
    description={
      <React.Fragment>
        <div>There are no plans yet!!</div>
        <div>Create new plans.</div>
      </React.Fragment>
    }
  />
);
