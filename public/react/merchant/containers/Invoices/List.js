import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'
import Modal from 'rzp/ui/Modal'

import { fetchInvoices } from 'merchant/modules/invoices'
import InvoicesList from 'merchant/components/Invoices/InvoicesList'
import ModalContainer from 'merchant/containers/ModalContainer'
import CreatePaymentLink from './CreatePaymentLink'

@connect(
  (state) => state.invoices.toJS(),
  { fetchInvoices }
)
export default class InvoicesListContainer extends ModalContainer {
  componentWillMount() {
    this.props.fetchInvoices()
  }

  render() {
    let { loading, invoices } = this.props

    return (
      <div>
        <Header title='Invoices (Link)'>
          <button
            class='pull-right btn btn-primary btn-rounded'
            onClick={this.openModal}
          >
            <i class='fa fa-plus'></i>
            <span>Create Payment Link</span>
          </button>
        </Header>

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <InvoicesList invoices={invoices} isLoading={loading} />
          </div>
        </div>

        <Modal
          isOpen={this.state.isModalOpen}
          onRequestClose={this.closeModal}
          closeTimeoutMS={300}
        >
          <CreatePaymentLink
            onSave={this.refreshList}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
