import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import Pager from 'rzp/ui/Pager'
import Alert from 'rzp/ui/Forms/Alert'
import ShowWhen from 'merchant/components/ShowWhen'
import InvoicesList from 'merchant/components/Invoices/InvoicesList'
import ListContainer from 'merchant/containers/ListContainer'
import CreatePaymentLink from './CreatePaymentLink'
import InvoiceListFilter from 'merchant/components/Invoices/InvoiceListFilter'
import * as InvoiceActions from 'merchant/modules/invoices/list'
import * as ModalActions from 'merchant/modules/modals'

@connect(
  (state) => state.invoices,
  { ...InvoiceActions, ...ModalActions }
)
export default class InvoicesListContainer extends ListContainer {
  constructor() {
    super(...arguments)
    this.editInvoice = ::this.editInvoice
    this.deleteInvoice = ::this.deleteInvoice
  }

  fetchEntityList(params) {
    return this.props.fetchInvoices(params)
  }

  showPaymentLinkModal(invoice = null) {
    this.props.openModal({
      component: <CreatePaymentLink
        invoice={invoice}
        onSave={(invoice) => {
          this.props.highLightInvoice(invoice.id)
        }}
        closeModal={this.props.closeModal}
      />
    })
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

  deleteInvoice(invoice) {
    this.context.confirm({
      message: 'Are you sure to delete the invoice ?',
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () => this.props.deleteInvoice(invoice).then(() => {
        this.setState({
          status: {
            type: 'success',
            message: 'Invoice deleted successfully'
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
    let { loading, invoices } = this.props
    let status = this.state.status

    return (
      <div class='react-root'>
        <ShowWhen notMyRole='support'>
          <div class='btn-toolbar'>
            <button
              class='btn btn-primary btn-rounded'
              onClick={() => this.showPaymentLinkModal()}
            >
              <i class='fa fa-plus'></i>
              <span>Create Payment Link</span>
            </button>

            <ShowWhen notMyRole='sellerapp' featureEnabled='Invoice'>
              <a
                href='#/app/invoices/new'
                class='btn btn-primary btn-rounded'
              >
                <i class='fa fa-plus'></i>
                <span>New Invoice</span>
              </a>
            </ShowWhen>
          </div>
        </ShowWhen>

        <div class='content-wrapper'>
          <div class='panel panel-default'>
            <div class='panel-heading'>
              Invoices List
            </div>

            <div class='panel-body'>
              <InvoiceListFilter
                form='InvoiceListFilter'
                count={this.state.count}
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
              onDelete={this.deleteInvoice}
            />

            <Pager
              count={this.state.count}
              skip={this.state.skip}
              length={invoices.length}
              onClick={this.fetchAll}
            />
          </div>
        </div>
      </div>
    )
  }
}
