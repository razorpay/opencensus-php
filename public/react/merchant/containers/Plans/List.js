import { Component } from 'react';
import { connect } from 'react-redux';
import TetherComponent from 'react-tether';
import PlansListFilter from 'merchant/components/Plans/ListFilter';
import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPlans as fetchAll } from 'merchant/modules/plans';
import * as ModalActions from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import PlanCreation from 'merchant/containers/Plans/New';
import ShowWhen from 'merchant/components/ShowWhen';
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
        <TetherComponent
          target="#subscriptions-header"
          attachment="top right"
          targetAttachment="top right"
          offset="-8px 0"
        >
          <div />{/* required by react-tether */}

          <ShowWhen notMyRole="support">
            <div class="btn-toolbar">
              <button
                class="pull-right btn btn-primary"
                onClick={() => this.showPlanModal()}
              >
                <i class="icon icon-plus" />
                <span>New Plan</span>
              </button>
            </div>
          </ShowWhen>
        </TetherComponent>

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
