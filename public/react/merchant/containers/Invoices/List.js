import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import Modal from 'rzp/ui/Modal'
import Pager from 'rzp/ui/Pager'
import Alert from 'rzp/ui/Forms/Alert'

import { fetchInvoices, highLightInvoice } from 'merchant/modules/invoices/list'
import InvoicesList from 'merchant/components/Invoices/InvoicesList'
import ModalContainer from 'merchant/containers/ModalContainer'
import CreatePaymentLink from './CreatePaymentLink'
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter'

@connect(
  (state) => state.invoices,
  { fetchInvoices, highLightInvoice }
)
export default class InvoicesListContainer extends ModalContainer {
  static skip = 0
  static count = 25
  static contextTypes = {
    ngRouter: PropTypes.object
  }

  constructor() {
    super(...arguments)
    this.state.invoiceToEdit = null
    this.fetchInvoices = ::this.fetchInvoices
    this.editInvoice = ::this.editInvoice
    this.search = ::this.search
  }

  componentWillMount() {
    this.fetchInvoices(this.getDefaultPageParams())
  }

  fetchInvoices(params) {
    if (params) {
      this.setState(params)
    }

    return this.props.fetchInvoices(params).then(() => {
      this.setState({ errors: null })
    }).catch(({ errors }) => {
      this.setState({ errors })
    })
  }

  getDefaultPageParams() {
    return {
      skip: InvoicesListContainer.skip,
      count: InvoicesListContainer.count
    }
  }

  search(params) {
    return this.fetchInvoices({
      ...this.getDefaultPageParams(),
      ...params
    })
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
              type='error'
              message={this.state.errors}
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
