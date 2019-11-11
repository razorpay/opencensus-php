import React, { Component } from 'react';
import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import CustomersList from 'merchant/components/Customers/CustomersList';
import CustomerCreation from 'merchant/containers/Customers/New';
import ListContainer from 'merchant/containers/ListContainer';
import * as CustomerActions from 'merchant/reducers/customers';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import { luminateRow } from 'merchant/reducers/app';
import TestModeBanner from 'merchant/containers/TestModeBanner';

@connect(state => ({ ...state.customers, mode: state.session.mode }), {
  ...CustomerActions,
  ...ModalActions,
  ...NotificationActions,
  luminateRow,
})
export default class CustomersListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchCustomers(params);
  }

  showCustomerModal = (customer = null) => {
    this.props.openModal({
      size: 'small',
      component: (
        <CustomerCreation
          customer={customer}
          onSave={this.highlightRowAndClose}
          closeModal={this.props.closeModal}
          askAddress={false}
        />
      ),
    });
  };

  highlightRowAndClose = customer => {
    this.props.luminateRow(customer.id);
    this.props.closeModal();
  };

  deleteCustomer = customer => {
    this.context.confirm({
      message: 'Are you sure to delete the customer?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        this.props
          .deleteCustomer(customer)
          .then(() => {
            this.setState({
              status: {
                type: 'success',
                message: 'Customer deleted successfully',
              },
            });
          })
          .catch(err => {
            this.setState({
              status: {
                type: 'error',
                message: err.errors,
              },
            });
          }),
    });
  };

  render() {
    let { loading, items, mode } = this.props;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <TestModeBanner />

        <HeaderAction>
          <ShowWhen
            additionalCondition={user =>
              (mode !== 'live' || !user.isRejected) &&
              user.isAllowedEdit('customers')
            }
          >
            <div class="btn-toolbar">
              <button
                class="pull-right btn btn-primary"
                onClick={() => this.showCustomerModal()}
              >
                <i class="i i-plus" />
                <span>New Customer</span>
              </button>
            </div>
          </ShowWhen>
        </HeaderAction>

        <Alert type={status.type} message={status.message} />

        <CustomersList
          customers={items}
          isLoading={loading}
          onEdit={this.showCustomerModal}
          onDelete={this.deleteCustomer}
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
