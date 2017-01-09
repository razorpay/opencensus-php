import React, { Component } from 'react'
import { connect } from 'react-redux'
import * as InvoiceActions from 'merchant/modules/invoices/details'
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail'

@connect(
  (state) => state.invoice,
  InvoiceActions
)
export default class InvoiceDetailContainer extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      statusMsg: {}
    }
    this.notifyCustomer = ::this.notifyCustomer
    this.issueInvoice = ::this.issueInvoice
  }

  componentWillMount() {
    this.props.fetchInvoice(this.props.id)
  }

  notifyCustomer(type) {
    return this.props.notifyCustomer(this.props.invoice, type).then((response) => {
      this.setState({
        statusMsg: {
          type: 'success',
          message: `${type === 'sms' ? 'SMS' : 'Email' } sent successfully`
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

  issueInvoice() {
    return this.props.issueInvoice(this.props.invoice).then((response) => {
      this.setState({
        statusMsg: {
          type: 'success',
          message: 'Invoice issued successfully'
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
    let { loading, invoice } = this.props
    let statusMsg = this.state.statusMsg

    return (
      <div class='react-root'>
        <div class='content-wrapper'>
          <InvoiceDetail
            invoice={invoice}
            isLoading={loading}
            statusMsg={statusMsg}
            onNotify={this.notifyCustomer}
            onIssue={this.issueInvoice}
          />
        </div>
      </div>
    )
  }
}
