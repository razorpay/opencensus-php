import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import * as InvoiceActions from 'merchant/modules/invoices/details';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail';
import IssueConfirmModal from 'merchant/containers/Invoices/IssueConfirmModal';
import { editPaymentLink } from 'merchant/containers/PaymentLinks/Links/model';
import { editPLInReduxList } from 'merchant/modules/invoices/list';

@connect(state => state.invoice, {
  ...InvoiceActions,
  ...ModalActions,
  ...NotificationsActions,
  editPLInReduxList,
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

  editPaymentLink = data => {
    return editPaymentLink(this.props.invoice.id, data)
      .then(resp => {
        if (resp.data) {
          this.props.editPLInReduxList(resp);

          this.props.showNotification({
            type: 'success',
            message: `${this.props.invoice.id} successfully Updated`,
          });

          return resp;
        } else {
          throw 'Some network issue occured';
        }
      })
      .catch(({ errors }) => {
        let err = errors || `Some Network error occured`;

        if (Array.isArray(err)) {
          err = err.map(e => {
            return e.toLowerCase().indexOf('status code') > -1 ? false : e;
          });
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });
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
        editPaymentLink={this.editPaymentLink}
      />
    );
  }
}
