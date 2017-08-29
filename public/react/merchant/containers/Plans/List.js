import { Component } from 'react';
import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import PlansListFilter from 'merchant/components/Plans/ListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPlans as fetchAll } from 'merchant/modules/plans';
import * as ModalActions from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import PlanCreation from 'merchant/containers/Plans/New';
import ShowWhen from 'merchant/components/ShowWhen';
import { NavLink } from 'react-router-dom';

import {
  planId,
  planName,
  planAmount,
  planBillingCycle,
  createdAt,
} from 'rzp/ui/item/pair';

@connect(state => state.plans, { fetchAll, luminateRow, ...ModalActions })
export default class PlansListContainer extends ListContainer {
  showPlanModal = (plan = null) => {
    this.props.openModal({
      component: (
        <PlanCreation
          plan={plan}
          onSave={this.highlightRowAndClose}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  highlightRowAndClose = plan => {
    this.props.luminateRow(plan.id);
    this.props.closeModal();
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
