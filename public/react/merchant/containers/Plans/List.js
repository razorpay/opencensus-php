import { Component } from 'react';
import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import PlansListFilter from 'merchant/components/Plans/ListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPlans as fetchAll } from 'merchant/modules/plans';
import * as ModalActions from 'rzp/modules/modals';
import ShowWhen from 'merchant/components/ShowWhen';
import { NavLink } from 'react-router-dom';
import {
  planId,
  planName,
  planAmount,
  planBillingCycle,
  createdAt,
} from 'rzp/ui/item/pair';
import { stringifyQueryParamsWithPipe } from 'rzp/utils/rzp-utils';

@connect(state => state.plans, { fetchAll, ...ModalActions })
export default class PlansListContainer extends ListContainer {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Go To - Plans',
    });
  }

  onSearchAnalytics = params => {
    const label = stringifyQueryParamsWithPipe(params);
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
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <ShowWhen notMyRole="support">
            <div class="btn-toolbar">
              <NavLink to="/plans/new">
                <button class="pull-right btn btn-primary">
                  <i class="icon icon-plus" />
                  <span>New Plan</span>
                </button>
              </NavLink>
            </div>
          </ShowWhen>
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
          {...this.props}
        />
      </div>
    );
  }
}
