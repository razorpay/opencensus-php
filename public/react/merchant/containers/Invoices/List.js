import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'

import { fetchInvoices } from 'merchant/modules/invoices'
import InvoicesList from 'merchant/components/Invoices/InvoicesList'

@connect(
  (state) => state.invoices.toJS(),
  { fetchInvoices }
)
export default class InvoicesListContainer extends Component {
  componentWillMount() {
    this.props.fetchInvoices()
  }

  render() {
    let { loading, invoices } = this.props

    return (
      <div>
        <Header title='Invoices' />
        <div className='content-wrapper'>
          <div className='panel panel-default'>
            <InvoicesList invoices={invoices} isLoading={loading} />
          </div>
        </div>
      </div>
    )
  }
}
