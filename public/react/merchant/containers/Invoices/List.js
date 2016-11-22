import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'
import Modal from 'rzp/ui/Modal'
import Pager from 'rzp/ui/Pager'

import { fetchInvoices } from 'merchant/modules/invoices/list'
import InvoicesList from 'merchant/components/Invoices/InvoicesList'
import ModalContainer from 'merchant/containers/ModalContainer'
import CreatePaymentLink from './CreatePaymentLink'

@connect(
  (state) => state.invoices.toJS(),
  { fetchInvoices }
)
export default class InvoicesListContainer extends ModalContainer {
  constructor() {
    super(...arguments)
    this.state.skip = 0
    this.state.count = 25
    this.state.highlightRowId = null

    this.fetchInvoices = ::this.fetchInvoices
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

    // params.type = 'link'
    this.props.fetchInvoices(params)
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
              highlightRow={(invoice) => invoice.id === this.state.highlightRowId}
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
            onSave={(invoice) => {
              this.setState({
                highlightRowId: invoice.id
              })
            }}
            closeModal={this.closeModal}
          />
        </Modal>
      </div>
    )
  }
}
