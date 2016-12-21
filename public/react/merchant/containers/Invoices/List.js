import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import Modal from 'rzp/ui/Modal'
import Pager from 'rzp/ui/Pager'
import Alert from 'rzp/ui/Forms/Alert'

import { fetchInvoices, highLightInvoice } from 'merchant/modules/invoices/list'
import InvoicesList from 'merchant/components/Invoices/InvoicesList'
import ListContainer from 'merchant/containers/ListContainer'
import CreatePaymentLink from './CreatePaymentLink'
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter'

@connect(
  (state) => state.invoices,
  { fetchInvoices, highLightInvoice }
)
export default class InvoicesListContainer extends ListContainer {
  constructor() {
    super(...arguments)
    this.state.invoiceToEdit = null
    this.editInvoice = ::this.editInvoice
  }

  fetchEntityList(params) {
    return this.props.fetchInvoices(params)
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
      this.context.ngRouter.transitionTo('app.invoices.edit', {
        id: invoice.id
      })
    }
  }

  render() {
    let { loading, invoices } = this.props
    let status = this.state.status

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
            <div class='panel-heading'>
              Invoices List
            </div>

            <div class='panel-body'>
              <InvoiceListFilter
                form='InvoiceListFilter'
                onSubmit={this.search}
              />
            </div>

            <Alert
              type={status.type}
              message={status.message}
            />

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
              onClick={this.fetchAll}
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
