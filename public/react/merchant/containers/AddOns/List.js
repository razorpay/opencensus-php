import { Component } from 'react';
import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import Alert from 'rzp/ui/Forms/Alert';
import Pager from 'rzp/ui/Pager';
import AddOnsListFilter from 'merchant/components/AddOns/ListFilter';
import AddOnsList from 'merchant/components/AddOns/List';
import ListContainer from 'merchant/containers/ListContainer';
import { fetchAddOns, deleteAddOn } from 'merchant/modules/addons';
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

@connect(
  state => {
    return {
      ...state.plans,
      ...state.app,
    };
  },
  {
    luminateRow,
    ...ModalActions,
  }
)
export default class AddOnsListContainer extends ListContainer {
  state = {
    items: [],
  };

  componentWillMount() {
    this.fetchAddOns();
  }

  fetchAddOns() {
    this.setState({
      loading: true,
      errors: null,
    });

    fetchAddOns()
      .then(response => {
        this.setState({
          loading: false,
          items: response.data.items,
          errors: null,
        });
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
          loading: false,
        });
      });
  }

  deleteAddOn = id => {
    deleteAddOn(id)
      .then(response => {
        this.fetchAddOns();
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  highlightRowAndClose = id => {
    this.props.closeModal();
    this.fetchAddOns(); // Fetching list t
    this.props.luminateRow(id);
  };

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

  render() {
    let { loading, items, errors, luminateRowId } = this.state;
    let statusMsg = {};

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
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
          luminateRowId={luminateRowId}
          onDelete={this.deleteAddOn}
        />

        <Pager
          count={this.state.count}
          skip={this.state.skip}
          length={items ? items.length : 0}
          onClick={this.paginate}
        />
      </div>
    );
  }
}
