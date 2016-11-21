import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'

import { fetchInvoice } from 'merchant/modules/invoices/details'
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail'

@connect(
  (state) => state.invoice.toJS(),
  { fetchInvoice }
)
export default class InvoiceDetailContainer extends Component {
  componentWillMount() {
    this.props.fetchInvoice(this.props.id)
  }

  render() {
    let { loading, invoice } = this.props

    return (
      <div>
        <Header title='Invoice Detail' />

        <div class='content-wrapper'>
          <InvoiceDetail invoice={invoice} isLoading={loading} />
        </div>
      </div>
    )
  }
}
