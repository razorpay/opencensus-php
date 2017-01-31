import React, { PropTypes, Component } from 'react'
import { connect } from 'react-redux'
import * as InvoiceActions from 'merchant/modules/invoices/details'
import * as ModalActions from 'merchant/modules/modals'
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail'
import SendInvoiceOptions from './SendInvoiceOptions'

@connect(
  (state) => state.invoice,
  {
    ...InvoiceActions,
    ...ModalActions
  }
)
export default class InvoiceDetailContainer extends Component {
  static contextTypes = {
    ngRouter: PropTypes.object,
    confirm: PropTypes.func
  }

  constructor() {
    super(...arguments)
    this.state = {
      statusMsg: {}
    }
    this.notifyCustomer = ::this.notifyCustomer
    this.issueInvoice = ::this.issueInvoice
    this.onSendInvoice = ::this.onSendInvoice
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
    this.context.confirm({
      message: 'Generating payment link will mark the invoice as issued. Do you want to proceed ?',
      affirmativeLabel: 'Generate',
      affirmativePendingLabel: 'Generating...',
      action: () => this.props.issueInvoice(this.props.invoice).catch((err) => {
        this.setState({
          status: {
            type: 'error',
            message: err.errors
          }
        })
      })
    })
  }

  onSendInvoice() {
    // this.props.openModal({
    //   component: <SendInvoiceOptions
    //     invoice={this.props.invoice}
    //     onSave={this.showSuccessMsg}
    //     onCancel={this.props.closeModal}
    //   />
    // })
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
            onSendInvoice={this.onSendInvoice}
          />
        </div>
      </div>
    )
  }
}
