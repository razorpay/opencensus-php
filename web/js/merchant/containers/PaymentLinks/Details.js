import React, { PropTypes, Component } from 'react';
import { connect } from 'react-redux';
import * as InvoiceActions from 'merchant/modules/invoices/details';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail';
import IssueConfirmModal from 'merchant/containers/Invoices/IssueConfirmModal';

@connect(state => state.invoice, {
  ...InvoiceActions,
  ...ModalActions,
  ...NotificationsActions,
})
export default class InvoiceDetailContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor() {
    super(...arguments);
    this.state = {
      statusMsg: {},
    };
  }

  componentWillMount() {
    this.props.fetchInvoice(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchInvoice(nextProps.id);
    }
  }

  issueInvoice = (props, notifyProps) => {
    let promises = [];

    if (notifyProps.email_notify) {
      promises.push(this.props.notifyCustomer(props, 'email'));
    }
    if (notifyProps.sms_notify) {
      promises.push(this.props.notifyCustomer(props, 'sms'));
    }

    return Promise.all(promises)
      .then(([emailStatus, smsStatus]) => {
        this.props.showNotification({
          type: 'success',
          message: 'Link sent successfully!',
        });
      })
      .catch(error => {
        this.setState({
          status: {
            type: 'error',
            message: error.errors,
          },
        });
      });
  };

  showIssueConfirmModal = () => {
    let customer = this.props.invoice.customer;
    if (!customer.contact && !customer.email) {
      this.props.showNotification({
        type: 'error',
        message: "Customer's contact/email was not provided",
      });
      return;
    }

    this.props.openModal({
      size: 'small',
      component: (
        <IssueConfirmModal
          isPaymentLink={true}
          customer={this.props.invoice.customer}
          onIssue={notifyProps => {
            window.rzpAnalytics({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Send - Payment Link',
              eventLabel: `payment_link_id=${this.props.invoice.id}`,
            });
            return this.issueInvoice(this.props.invoice, notifyProps);
          }}
          onMount={() => {
            window.rzpAnalytics({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Open Form - Send Link',
              eventLabel: `payment_link_id=${this.props.invoice.id}`,
            });
          }}
          onUnmount={() => {
            window.rzpAnalytics({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Close Form - Send Link',
              eventLabel: `payment_link_id=${this.props.invoice.id}`,
            });
          }}
        />
      ),
    });
  };

  cancelInvoice = () => {
    let invoice = this.props.invoice;
    this.context.confirm({
      header: 'Cancel Link?',
      message: () => (
        <div class="text-semi-muted">
          <p>
            The Link will be cancelled and the customer will not be able to pay
            for it.
          </p>
        </div>
      ),
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, don't!",
      action: () => {
        return this.props
          .cancelInvoice(invoice)
          .then(invoice => {
            window.rzpAnalytics({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Submit Form - Cancel Payment Link',
              eventLabel: `payment_link_id=${invoice.id}`,
            });

            window.rzpAnalytics({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Close Form - Cancel Payment Link',
              eventLabel: `payment_link_id=${invoice.id}`,
            });

            this.props.showNotification({
              type: 'success',
              message: 'Link cancelled!',
            });
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
      onMount: () => {
        window.rzpAnalytics({
          eventCategory: 'Dashboard - Payment Links',
          eventAction: 'Open Form - Cancel Payment Link',
          eventLabel: `payment_link_id=${invoice.id}`,
        });
      },
      abort: () => {
        window.rzpAnalytics({
          eventCategory: 'Dashboard - Payment Links',
          eventAction: 'Close Form - Cancel Payment Link',
          eventLabel: `payment_link_id=${invoice.id}`,
        });
      },
    });
  };

  render() {
    let { loading, invoice } = this.props;
    let statusMsg = this.state.statusMsg;

    return (
      <InvoiceDetail
        invoice={invoice}
        isLoading={loading}
        statusMsg={statusMsg}
        onIssue={this.showIssueConfirmModal}
        onCancel={this.cancelInvoice}
      />
    );
  }
}
