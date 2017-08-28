import { Component } from 'react';
import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import Alert from 'rzp/ui/Forms/Alert';
import Pager from 'rzp/ui/Pager';
import AddOnsListFilter from 'merchant/components/AddOns/ListFilter';
import AddOnsList from 'merchant/components/AddOns/List';
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

  actionOnAddOns = (type, id) => {
    switch (type) {
      case 'delete':
        break; // dispatcher call
      case 'edit':
        break; // open edit modal
    }
  };

  render() {
    let { loading, items, error } = this.props;
    items = [
      {
        id: 'add_12323asdad1ad',
        date: 1523232123,
        amount: 123232,
        name: 'flash',
        status: 'success',
        short_url: '/addon/12323',
        isEditable: true,
      },
      {
        id: 'add_12323asbblod1d',
        date: 1523232123,
        amount: 219232,
        name: 'batman',
        status: 'success',
        short_url: '/addon/12223',
        isEditable: false,
      },
    ];
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
      };
    }

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

        <Alert type={statusMsg.type} message={statusMsg.message} />
        {/*addonId, addonName, addonAmount, createdAt*/}

        <AddOnsList
          addons={items}
          isLoading={loading}
          onAction={this.actionOnAddOns}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={items.length}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
