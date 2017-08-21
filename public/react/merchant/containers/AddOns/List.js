import { Component } from 'react';
import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import AddOnsListFilter from 'merchant/components/AddOns/ListFilter';

import DataTable from 'rzp/ui/Table/DataTable';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchPlans as fetchAll } from 'merchant/modules/plans';
import * as ModalActions from 'rzp/modules/modals';
import { luminateRow } from 'merchant/modules/app';
import AddOnCreation from 'merchant/containers/AddOns/New';
import ShowWhen from 'merchant/components/ShowWhen';
import {
  planId,
  planName,
  planAmount,
  planBillingCycle,
  createdAt,
} from 'rzp/ui/item/pair';

@connect(state => state.plans, { fetchAll, luminateRow, ...ModalActions })
export default class AddOnsListContainer extends ListContainer {
  showAddOnModal = (addon = null) => {
    this.props.openModal({
      size: 'small',
      component: (
        <AddOnCreation
          addon={addon}
          onSave={this.highlightRowAndClose}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  highlightRowAndClose = addon => {
    this.props.luminateRow(addon.id);
    this.props.closeModal();
  };

  render() {
    let { loading, items, error } = this.props;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <ShowWhen notMyRole="support">
            <div class="btn-toolbar">
              <button
                class="pull-right btn btn-primary"
                onClick={() => this.showAddOnModal()}
              >
                <i class="icon icon-plus" />
                <span>New Add On</span>
              </button>
            </div>
          </ShowWhen>
        </HeaderAction>

        <AddOnsListFilter
          form="addonsListFilter"
          count={this.state.count}
          onSubmit={this.search}
        />
        {/*addonId, addonName, addonAmount, createdAt*/}
      </div>
    );
  }
}
