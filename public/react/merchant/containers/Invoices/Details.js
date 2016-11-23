import React, { Component } from 'react'
import { connect } from 'react-redux'
import Header from 'rzp/ui/Header'
import { fetchInvoice, notifyCustomer } from 'merchant/modules/invoices/details'
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail'

@connect(
  (state) => state.invoice.toJS(),
  { fetchInvoice, notifyCustomer }
)
export default class InvoiceDetailContainer extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      statusMsg: {}
    }
    this.notifyCustomer = ::this.notifyCustomer
  }

  componentWillMount() {
    this.props.fetchInvoice(this.props.id)
  }

  notifyCustomer(type) {
    return this.props.notifyCustomer(this.props.id, type).then((response) => {
      this.setState({
        statusMsg: {
          type: 'success',
          message: `${type === 'sms' ? 'SMS' : 'EMAIL' } sent successfully`
        }
      })
    }).catch((error) => {
      this.setState({
        statusMsg: {
          type: 'error',
          message: error.errors
        }
      })
    })
  }

  render() {
    let { loading, invoice, error } = this.props
    let statusMsg = this.state.statusMsg
    if (error) {
      statusMsg = {
        type: 'error',
        message: error
      }
    }

    return (
      <div>
        <Header title='Invoice Detail' />

        <div class='content-wrapper'>
          <InvoiceDetail
            invoice={invoice}
            isLoading={loading}
            statusMsg={statusMsg}
            onNotify={this.notifyCustomer}
          />
        </div>
      </div>
    )
  }
}
