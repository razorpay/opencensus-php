import React, { PropTypes, Component } from 'react'
import { connect } from 'react-redux'
import * as InvoiceActions from 'merchant/modules/invoices/details'
import * as ModalActions from 'merchant/modules/modals'
import * as NotificationsActions from 'merchant/modules/notifications'
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail'
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
    this.expireInvoice = ::this.expireInvoice
  }

  componentWillMount() {
    this.props.fetchInvoice(this.props.id)
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

  expireInvoice() {
    let invoice = this.props.invoice
    this.context.confirm({
      header: 'Expire Invoice?',
      message: () => (
        <div class='text-semi-muted'>
          <p>The Link will be expired and the customer will not be able to pay for it.</p>
        </div>
      ),
      affirmativeLabel: 'Yes, Expire',
      affirmativePendingLabel: 'Expiring...',
      abortLabel: 'No, don\'t!',
      action: () => {
        return this.props.expireInvoice(invoice).then((invoice) => {
          this.props.showNotification({
            type: 'success',
            message: 'Link expired!'
          })
        }).catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors
          })
        })
      }
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
            onExpire={this.expireInvoice}
          />
        </div>
      </div>
    )
  }
}
