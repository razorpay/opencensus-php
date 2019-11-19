import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import * as InvoiceActions from 'merchant/reducers/invoices/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail';
import IssueConfirmModal from 'merchant/containers/Invoices/IssueConfirmModal';
import { editPaymentLink } from 'merchant/containers/PaymentLinks/Links/model';
import { updatePLInReduxList } from 'merchant/reducers/invoices/list';
import { keysToSentence } from 'common/utils/rzp-utils';

import { MIN_AMOUNT_TEXT } from '../Edit/EditMinimumAmount';

@connect(
  state => ({
    ...state.invoice,
    ...state.session,
  }),
  {
    ...InvoiceActions,
    ...ModalActions,
    ...NotificationsActions,
    updatePLInReduxList,
  }
)
export default class InvoiceDetailContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(...arguments);
    this.state = {
      statusMsg: {},
      isAutoRemindersUpdating: true,
      nextReminders: [],
    };

    // recording new payments links creation UI form in hotjar
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'payment_links_v2_details_open');
      window.hj('tagRecording', ['payment_links_v2_details_open']);
    }
  }

  componentWillMount() {
    this.props.fetchInvoice(this.props.id);
    this.fetchInvoiceRemindersList();
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchInvoice(nextProps.id);
    }
  }

  fetchInvoiceRemindersList = () => {
    InvoiceActions.fetchInvoiceRemindersList(this.props.id).then(resp => {
      this.setState({
        nextReminders: resp.data.next_run_at || [],
        isAutoRemindersUpdating: false,
      });
    });
  };

  onChangeSendAutoReminder = event => {
    this.setState({
      isAutoRemindersUpdating: true,
    });

    this.editPaymentLink({
      reminder_enable: event.target.value === '1',
    }).then(resp => {
      this.fetchInvoiceRemindersList();

      return resp;
    });
  };

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
            this.props.updatePLInReduxList({ data: invoice }, false);
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
            if (
              !errors ||
              (errors instanceof Array === true &&
                (!errors.length || !errors[0]))
            ) {
              errors = 'Some network error has occurred';
            }

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
          this.props.updatePLInReduxList(resp, false);

          let successMsgKey,
            d = data;
          if (d.hasOwnProperty('first_payment_min_amount')) {
            d = { ...data };

            d[MIN_AMOUNT_TEXT.split(' ').join('_')] =
              d.first_payment_min_amount;
            delete d.first_payment_min_amount;
          }

          if (d.hasOwnProperty('reminder_enable')) {
            this.props.showNotification({
              type: 'success',
              message: `Auto reminders has been ${
                d.reminder_enable ? 'enabled' : 'disabled'
              } successfully`,
            });

            return this.props.fetchInvoice(this.props.id);
          } else {
            this.props.showNotification({
              type: 'success',
              message: `${keysToSentence(d)} updated successfully`,
            });
          }

          return resp;
        } else {
          throw 'Some Network error occured';
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          errors.length &&
            errors.forEach(e => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some Network error occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  };

  render() {
    let { loading, invoice, user } = this.props;
    let statusMsg = this.state.statusMsg;

    return (
      <InvoiceDetail
        user={user}
        invoice={invoice}
        isLoading={loading}
        statusMsg={statusMsg}
        nextReminders={this.state.nextReminders}
        onIssue={this.showIssueConfirmModal}
        onCancel={this.cancelInvoice}
        editPaymentLink={this.editPaymentLink}
        isRoleAllowedEdit={user.isAllowedEdit('payment_links')}
        onChangeSendAutoReminder={this.onChangeSendAutoReminder}
        isAutoRemindersUpdating={this.state.isAutoRemindersUpdating}
        isMinimumFirstPaymentEnabled={user.isMinimumFirstPaymentEnabled}
      />
    );
  }
}
