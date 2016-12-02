import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'
import Modal from 'rzp/ui/Modal'
import Pager from 'rzp/ui/Pager'

import { fetchInvoices, highLightInvoice } from 'merchant/modules/invoices/list'
import InvoicesList from 'merchant/components/Invoices/InvoicesList'
import ModalContainer from 'merchant/containers/ModalContainer'
import CreatePaymentLink from './CreatePaymentLink'

@connect(
  (state) => state.invoices.toJS(),
  { fetchInvoices, highLightInvoice }
)
export default class InvoicesListContainer extends ModalContainer {
  constructor() {
    super(...arguments)
    this.state.skip = 0
    this.state.count = 25
    this.state.invoice = null
    this.fetchInvoices = ::this.fetchInvoices
    this.editInvoice = ::this.editInvoice
  }

  componentWillMount() {
    this.fetchInvoices()
  }

  fetchInvoices(params) {
    if (params) {
      this.setState(params)
    }

    params = params || {
      count: this.state.count,
      skip: this.state.skip
    }

    this.props.fetchInvoices(params)
  }

  editInvoice(invoice) {
    if (invoice.type === 'link') {
      this.setState({
        invoice
      })
      this.openModal()
    }
  }

  render() {
    let { loading, invoices } = this.props

    return (
      <div>
        <Header title='Invoices'>
          <div class='btn-toolbar pull-right'>
            <button
              class='btn btn-primary btn-rounded'
              onClick={this.openModal}
            >
              <i class='fa fa-plus'></i>
              <span>Create Payment Link</span>
            </button>
            <a
              href='#/app/invoices/new'
              class='btn btn-primary btn-rounded'
            >
              <i class='fa fa-plus'></i>
              <span>New Invoice</span>
            </a>
          </div>
        </Header>

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <InvoicesList
              invoices={invoices}
              isLoading={loading}
              highlightRow={(invoice) => invoice.id === this.props.highLightInvoiceId}
              onEdit={this.editInvoice}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={invoices.length}
              onClick={this.fetchInvoices}
            />
          </div>
        </div>

        <Modal
          isOpen={this.state.isModalOpen}
          onRequestClose={this.closeModal}
          closeTimeoutMS={300}
        >
          <CreatePaymentLink
            invoice={this.state.invoice}
            onSave={(invoice) => {
              this.props.highLightInvoice(invoice.id)
            }}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
