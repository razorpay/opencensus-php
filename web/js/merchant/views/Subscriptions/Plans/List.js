import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';
import { RZPFeatures } from 'merchant/helpers/data';

import { planId, planName, planAmount, planBillingCycle, createdAt } from 'common/ui/item/pair';
import HeaderAction from 'common/ui/HeaderAction';
import DataTable from 'common/ui/Table/DataTable';

import { fetchSubscriptions } from 'merchant/reducers/subscriptions';
import { fetchPlans as fetchAll } from 'merchant/reducers/plans';
import * as ModalActions from 'merchant_common/reducers/modals';

import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';
import EmptyList from 'merchant/components/EmptyList';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';

import { getSubscriptionQuickGuideIsClosed } from 'merchant/views/Subscriptions/QuickGuide';

import PlansListFilter from 'merchant/views/Subscriptions/Plans/components/ListFilter';
import ListContainer from 'merchant/containers/ListContainer';
import analytics from '../analytics';

@connect(
  (state) => ({
    ...state.plans,
    user: state.session.user,
    subscriptions: state.subscriptions,
  }),
  {
    ...ModalActions,
    fetchAll,
    fetchSubscriptions,
  },
)
export default class PlansListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Go To - Plans',
    });

    const isQuickGuideClosed = getSubscriptionQuickGuideIsClosed(this.props);

    if (!this.props.subscriptions.items.length && !isQuickGuideClosed) {
      this.props.fetchSubscriptions({ count: 1 });
    }
  }

  onSearchAnalytics = (params) => {
    const label = getKeysSeparatedByPipe(params);

    if (label && label.length > 0) {
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Subscriptions',
        eventAction: 'Search - Plans',
        eventLabel: label,
      });
      analytics.track(`plan.search.${label}`);
    }
  };

  onClearAnalytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Clear Search Params - Plans',
    });
    analytics.track('plan.search.clear');
  };

  onClickPaginate = (params, type) => {
    analytics.track(`plan.browse.${type}`);

    this.paginate(params);
  };

  render() {
    const { docUrl } = this.props;

    return (
      <div className="content-wrapper">
        <HeaderAction responsive>
          <div className="btn-toolbar pull-right">
            <TakeATourButton
              feature={RZPFeatures.SUBSCRIPTIONS}
              onClick={() => analytics.track('plan.search.help')}
            />

            {docUrl && (
              <DocsLink url={docUrl} onClick={() => analytics.track('plan.search.documentation')} />
            )}

            <ShowWhen additionalCondition={(user) => user.isAllowedEdit('subscriptions')}>
              <span className="cta-container">
                <NavLink
                  to="/plans/new"
                  onClick={() => {
                    analytics.track('plan.create.initiate');
                  }}
                >
                  <button className="pull-right btn btn-primary">
                    <i className="i i-plus" />
                    <span>New Plan</span>
                  </button>
                </NavLink>
              </span>
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
          <div>There are no plans yet!!</div>
          <div>Create new plans.</div>
        </>
      }
    />
  );
}
