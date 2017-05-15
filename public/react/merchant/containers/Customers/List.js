import React, { Component } from 'react';
import { connect } from 'react-redux';
import Pager from 'rzp/ui/Pager';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import CustomersList from 'merchant/components/Customers/CustomersList';
import CustomerCreation from 'merchant/containers/Customers/New';
import ListContainer from 'merchant/containers/ListContainer';
import * as CustomerActions from 'merchant/modules/customers';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';

@connect(state => state.customers, {
  ...CustomerActions,
  ...ModalActions,
  ...NotificationActions,
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
        />
      ),
    });
  };

  highlightRowAndClose = customer => {
    this.props.highlightCustomerRow(customer);
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
    let { loading, customers, highlightRowId } = this.props;
    let status = this.state.status;

    return (
      <div class="react-root">
        <ShowWhen notMyRole="support">
          <div class="btn-toolbar">
            <button
              class="pull-right btn btn-primary btn-rounded"
              onClick={() => this.showCustomerModal()}
            >
              <i class="fa fa-plus" />
              <span>New Customer</span>
            </button>
          </div>
        </ShowWhen>

        <div class="content-wrapper">
          <Alert type={status.type} message={status.message} />

          <CustomersList
            customers={customers}
            isLoading={loading}
            highlightRow={customer => customer.id === highlightRowId}
            onEdit={this.showCustomerModal}
            onDelete={this.deleteCustomer}
          />

          <Pager
            count={this.state.count}
            skip={this.state.skip}
            length={customers.length}
            onClick={this.paginate}
          />
        </div>
      </div>
    );
  }
}
