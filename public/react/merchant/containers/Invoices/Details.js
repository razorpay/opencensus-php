import React, { PropTypes, Component } from 'react'
import { connect } from 'react-redux'
import * as InvoiceActions from 'merchant/modules/invoices/details'
import * as ModalActions from 'merchant/modules/modals'
import * as NotificationsActions from 'merchant/modules/notifications'
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail'
import SendInvoiceOptions from './SendInvoiceOptions'
import IssueConfirmModal from './IssueConfirmModal'

@connect(
  (state) => state.invoice,
  {
    ...InvoiceActions,
    ...ModalActions,
    ...NotificationsActions
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
    this.showIssueConfirmModal = ::this.showIssueConfirmModal
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

  issueInvoice(props, notifyProps) {
    let promises = []

    if (notifyProps.email_notify) {
      promises.push(this.props.notifyCustomer(props, 'email'))
    }
    if (notifyProps.sms_notify) {
      promises.push(this.props.notifyCustomer(props, 'sms'))
    }

    return Promise.all(promises).then(([emailStatus, smsStatus]) => {
      this.props.showNotification({
        type: 'success',
        message: 'Link sent successfully!'
      })
    }).catch((error) => {
      this.setState({
        status: {
          type: 'error',
          message: error.errors
        }
      })
    })
  }

  showIssueConfirmModal() {
    let customer = this.props.invoice.customer
    if (!customer.contact && !customer.email) {
      this.props.showNotification({
        type: 'error',
        message: 'Customer\'s contact/email was not provided'
      })
      return
    }

    this.props.openModal({
      size: 'small',
      component: <IssueConfirmModal
        customer={this.props.invoice.customer}
        onIssue={(notifyProps) => {
          return this.issueInvoice(this.props.invoice, notifyProps)
        }}
      />
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
            onIssue={this.showIssueConfirmModal}
          />
        </div>
      </div>
    )
  }
}
