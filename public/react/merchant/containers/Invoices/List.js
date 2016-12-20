import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import Modal from 'rzp/ui/Modal'
import Pager from 'rzp/ui/Pager'

import { fetchInvoices, highLightInvoice } from 'merchant/modules/invoices/list'
import InvoicesList from 'merchant/components/Invoices/InvoicesList'
import ModalContainer from 'merchant/containers/ModalContainer'
import CreatePaymentLink from './CreatePaymentLink'

@connect(
  (state) => state.invoices,
  { fetchInvoices, highLightInvoice }
)
export default class InvoicesListContainer extends ModalContainer {
  static contextTypes = {
    ngRouter: PropTypes.object
  }

  constructor() {
    super(...arguments)
    this.state.skip = 0
    this.state.count = 25
    this.state.invoiceToEdit = null
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

  showPaymentLinkModal(invoice = null) {
    this.setState({
      invoiceToEdit: invoice
    })
    this.openModal()
  }

  editInvoice(invoice) {
    if (invoice.type === 'link') {
      this.showPaymentLinkModal(invoice)
    } else if (invoice.type === 'invoice') {
      this.context.ngRouter.transitionTo('app.invoicesedit', {
        id: invoice.id
      })
    }
  }

  render() {
    let { loading, invoices } = this.props

    return (
      <div class='react-root'>
        <div class='btn-toolbar'>
          <button
            class='btn btn-primary btn-rounded'
            onClick={() => this.showPaymentLinkModal()}
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
            invoice={this.state.invoiceToEdit}
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
