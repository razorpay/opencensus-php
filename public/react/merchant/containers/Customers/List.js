import React, { Component } from 'react'
import { connect } from 'react-redux'
import Modal from 'rzp/ui/Modal'
import Header from 'rzp/ui/Header'
import { fetchCustomers } from 'merchant/modules/customers'
import CustomersList from 'merchant/components/Customers/CustomersList'
import CustomerCreation from 'merchant/containers/Customers/New'
import ModalContainer from 'merchant/containers/ModalContainer'

@connect(
  (state) => state.customers.toJS(),
  { fetchCustomers }
)
export default class CustomersListContainer extends ModalContainer {
  constructor() {
    super(...arguments)
    this.showCustomerModal = ::this.showCustomerModal
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

  render() {
    let { loading, customers } = this.props

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
          <div class='panel panel-default'>
            <CustomersList
              customers={customers}
              isLoading={loading}
              onEdit={this.showCustomerModal}
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
            onSave={this.closeModal}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
