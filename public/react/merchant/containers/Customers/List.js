import React, { Component } from 'react'
import { connect } from 'react-redux'
import { reduxForm, formValueSelector } from 'redux-form'
import Modal from 'rzp/ui/modal'
import Header from 'rzp/ui/Header'
import { fetchCustomers } from 'merchant/modules/customers'
import CustomersList from 'merchant/components/Customers/CustomersList'
import CustomerCreation from 'merchant/containers/Customers/New'
import ModalContainer from 'merchant/containers/ModalContainer'

@connect(
  (state) => state.customers.toJS(),
  { fetchCustomers }
)
@reduxForm({
  form: 'newCustomer',
})
export default class CustomersListContainer extends ModalContainer {
  constructor() {
    super(...arguments)
    this.editCustomer = ::this.editCustomer
    this.deleteCustomer = ::this.deleteCustomer
  }

  componentWillMount() {
    this.props.fetchCustomers()
  }

  editCustomer(customer) {
    this.props.initialize(customer)
    this.openModal()
  }

  deleteCustomer() {

  }

  render() {
    let { loading, customers } = this.props

    return (
      <div>
        <Header title='Customers'>
          <button
            class='pull-right btn btn-primary btn-rounded'
            onClick={this.openModal}
          >
            <i class='fa fa-plus'></i>
            <span>New Customer</span>
          </button>
        </Header>

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <CustomersList
              customers={customers}
              isLoading={loading}
              onEdit={this.editCustomer}
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
            onSave={this.closeModal}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
