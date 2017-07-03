import { Component } from 'react';
import { connect } from 'react-redux';

import PlansListFilter from 'merchant/components/Plans/ListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPlans as fetchAll } from 'merchant/modules/plans';

import {
  planId,
  planName,
  planAmount,
  planBillingCycle,
  createdAt,
} from 'rzp/ui/item/pair';

@connect(state => state.plans, { fetchAll })
export default class PlansListContainer extends ListContainer {
  render() {
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">
        <PlansListFilter
          form="plansListFilter"
          count={this.state.count}
          onSubmit={this.search}
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
