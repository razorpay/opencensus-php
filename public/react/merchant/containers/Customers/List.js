import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import Modal from 'rzp/ui/Modal'
import Pager from 'rzp/ui/Pager'
import Alert from 'rzp/ui/Forms/Alert'
import Role from 'merchant/components/Role'
import CustomersList from 'merchant/components/Customers/CustomersList'
import CustomerCreation from 'merchant/containers/Customers/New'
import ListContainer from 'merchant/containers/ListContainer'
import * as CustomerActions from 'merchant/modules/customers'

@connect(
  (state) => state.customers,
  CustomerActions
)
export default class CustomersListContainer extends ListContainer {
  constructor() {
    super(...arguments)
    this.showCustomerModal = ::this.showCustomerModal
    this.highlightRowAndClose = ::this.highlightRowAndClose
    this.deleteCustomer = ::this.deleteCustomer
  }

  fetchEntityList(params) {
    return this.props.fetchCustomers(params)
  }

  showCustomerModal(customer = null) {
    this.setState({
      customerToEdit: customer
    })
    this.openModal()
  }

  highlightRowAndClose(customer) {
    this.props.highlightCustomerRow(customer)
    this.closeModal()
  }

  deleteCustomer(customer) {
    this.context.confirm({
      message: 'Are you sure to delete the customer?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () => this.props.deleteCustomer(customer).then(() => {
        this.setState({
          status: {
            type: 'success',
            message: 'Customer deleted successfully'
          }
        })
      }).catch((err) => {
        this.setState({
          status: {
            type: 'error',
            message: err.errors
          }
        })
      })
    })
  }

  render() {
    let { loading, customers, highlightRowId } = this.props
    let status = this.state.status

    return (
      <div class='react-root'>
        <Role notMyRole='support'>
          <div class='btn-toolbar'>
            <button
              class='pull-right btn btn-primary btn-rounded'
              onClick={() => this.showCustomerModal()}
            >
              <i class='fa fa-plus'></i>
              <span>New Customer</span>
            </button>
          </div>
        </Role>

        <div class='content-wrapper'>
          <Alert
            type={status.type}
            message={status.message}
          />

          <div class='panel panel-default'>
            <CustomersList
              customers={customers}
              isLoading={loading}
              highlightRow={(customer) => customer.id === highlightRowId}
              onEdit={this.showCustomerModal}
              onDelete={this.deleteCustomer}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={customers.length}
              onClick={this.fetchAll}
            />
          </div>
        </div>

        <Modal
          isOpen={this.state.isModalOpen}
          onRequestClose={this.closeModal}
          closeTimeoutMS={300}
        >
          <CustomerCreation
            customer={this.state.customerToEdit}
            onSave={this.highlightRowAndClose}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
