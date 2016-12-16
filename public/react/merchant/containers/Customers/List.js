import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import Modal from 'rzp/ui/Modal'
import Header from 'rzp/ui/Header'
import Alert from 'rzp/ui/Forms/Alert'
import CustomersList from 'merchant/components/Customers/CustomersList'
import CustomerCreation from 'merchant/containers/Customers/New'
import ModalContainer from 'merchant/containers/ModalContainer'
import * as CustomerActions from 'merchant/modules/customers'

@connect(
  (state) => state.customers.toJS(),
  CustomerActions
)
export default class CustomersListContainer extends ModalContainer {
  static contextTypes = {
    confirm: PropTypes.func
  }

  constructor() {
    super(...arguments)
    this.state.status = {}

    this.showCustomerModal = ::this.showCustomerModal
    this.highlightRowAndClose = ::this.highlightRowAndClose
    this.deleteCustomer = ::this.deleteCustomer
  }

  componentWillMount() {
    this.props.fetchCustomers()
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
    this.context.confirm('Are you sure to delete the customer?').then(() => {
      this.props.deleteCustomer(customer).then((response) => {
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
      <div>
        <Header title='Customers'>
          <button
            class='pull-right btn btn-primary btn-rounded'
            onClick={() => this.showCustomerModal()}
          >
            <i class='fa fa-plus'></i>
            <span>New Customer</span>
          </button>
        </Header>

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
