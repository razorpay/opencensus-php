import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import * as InvoiceActions from 'merchant/reducers/invoices/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import Details from './Details';
import IssueConfirmModal from 'merchant/views/Invoices/Invoices/components/IssueConfirmModal';
import { editPaymentLink } from 'merchant/containers/PaymentLinks/Links/model';
import { updatePLInReduxList } from 'merchant/reducers/invoices/list';
import { keysToSentence, findBy } from 'common/utils/rzp-utils';
import {
  fetchReminders,
  fetchRemindersMerchantConfigs,
} from 'merchant/reducers/reminders';

import { MIN_AMOUNT_TEXT } from '../../Edit/EditMinimumAmount';

@connect(
  state => ({
    ...state.invoice,
    ...state.session,
    reminders: state.reminders,
  }),
  {
    ...InvoiceActions,
    ...ModalActions,
    ...NotificationsActions,
    updatePLInReduxList,
    fetchReminders,
    fetchRemindersMerchantConfigs,
  }
)
@RTracking(() => window.rzpQ.component('InvoiceDetailContainer'))
export default class InvoiceDetailContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(...arguments);
    this.state = {
      statusMsg: {},
      isAutoRemindersUpdating: true,
      isPaymentLinksRemindersEnabled: false,
      nextReminders: [],
    };

    // recording new payments links creation UI form in hotjar
    if (typeof window.hj === 'function') {
      window.hj('trigger', 'payment_links_v2_details_open');
      window.hj('tagRecording', ['payment_links_v2_details_open']);
    }
  }

  componentWillMount() {
    this.fetchDataForInvoice();
  }

  componentDidMount() {
    this.trackPaymentLinkDetailsView('pl.update.details_view');
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchDataForInvoice(nextProps.id);
    }
  }

  trackPaymentLinkDetailsView = (event, options) => {
    return this.props.tracking.trackEvent(
      window.rzpQ.paymentLinks().interaction(event, {
        ...options,
        origin: 'dashboard',
      })
    );
  };

  fetchDataForInvoice = (id = this.props.id) => {
    this.props.fetchInvoice(id);
    this.fetchInvoiceRemindersList(id);
  };

  fetchInvoiceRemindersList = (id = this.props.id) => {
    if (!this.props.user.isRemindersEnabled) {
      return;
    }

    const promiseList = [];

    if (!this.props.reminders.reminders.items.length) {
      promiseList.push(this.props.fetchReminders());
    } else {
      promiseList.push(Promise.resolve());
    }

    promiseList.push(InvoiceActions.fetchInvoiceRemindersList(id));

    Promise.all(promiseList).then(respList => {
      const paymentLinksRemindersSettings =
        findBy(
          this.props.reminders.reminders.items,
          'namespace',
          'payment_link'
        ) || {};

      this.setState({
        isPaymentLinksRemindersEnabled: paymentLinksRemindersSettings.active,
        isAutoRemindersUpdating: false,
        nextReminders: respList[1].data.next_run_at || [],
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
        this.props.tracking.trackEvent(
          window.rzpQ.paymentLinks().interaction('pl.resend.issue', {
            origin: 'dashboard',
          })
        );

        this.props.showNotification({
          type: 'success',
          message: 'Link sent successfully!',
          onCloseClick: () => {
            this.props.tracking.trackEvent(
              window.rzpQ
                .paymentLinks()
                .interaction('pl.resend.issue.success', {
                  origin: 'dashboard',
                  close: 1,
                })
            );
          },
          onTimeOutClose: () => {
            this.props.tracking.trackEvent(
              window.rzpQ
                .paymentLinks()
                .interaction('pl.resend.issue.success', {
                  origin: 'dashboard',
                  close: 0,
                })
            );
          },
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

    this.props.tracking.trackEvent(
      window.rzpQ.paymentLinks().interaction('pl.resend.start', {
        origin: 'dashboard',
      })
    );

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
          onCloseClick={() => {
            this.props.tracking.trackEvent(
              window.rzpQ.paymentLinks().interaction('pl.resend.close', {
                origin: 'dashboard',
              })
            );
          }}
          onFieldChange={event => {
            this.props.tracking.trackEvent(
              window.rzpQ
                .paymentLinks()
                .interaction(`pl.resend.${event.target.name}`, {
                  origin: 'dashboard',
                })
            );
          }}
        />
      ),
    });
  };

  cancelInvoice = () => {
    let invoice = this.props.invoice;

    this.trackPaymentLinkDetailsView('pl.update.deactivate');

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
              onCloseClick: () => {
                this.trackPaymentLinkDetailsView('pl.deactivate.success', {
                  close: 1,
                });
              },
              onTimeOutClose: () => {
                this.trackPaymentLinkDetailsView('pl.deactivate.success', {
                  close: 0,
                });
              },
            });

            this.trackPaymentLinkDetailsView('pl.update.deactivate_confirm');
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

        this.trackPaymentLinkDetailsView('pl.update.deactivate_abort');
      },
    });
  };

  trackEditReceipt = (changeType, type, modified) => {
    if (changeType === 'Edit Receipt') return;

    this.trackPaymentLinkDetailsView(
      `pl.update.${changeType ? 'receipt_abort' : 'receipt_confirm'}`,
      { modified }
    );
  };

  trackEditExpiry = (changeType, type, modified) => {
    if (changeType === 'Edit Expiry') return;

    let trackEvent = '';

    if (changeType === 'Edit Expiry (Saved)' && !type) {
      trackEvent = 'expiry_tick';
    }

    if (type === 'Cancel Expiry') {
      trackEvent = 'expiry_cancel';
    }

    this.trackPaymentLinkDetailsView(`pl.update.${trackEvent}`, { modified });
  };

  trackEditNotes = (changeType, modified) => {
    if (changeType === 'Save Notes') {
      this.trackPaymentLinkDetailsView(`pl.update.notes`, { modified });
    }

    if (changeType === 'Delete Notes (Confirmed)') {
      this.trackPaymentLinkDetailsView(`pl.update.notes_closed`);
    }

    if (changeType === 'Delete Notes (Cancelled)') {
      this.trackPaymentLinkDetailsView(`pl.update.notes.close`);
    }
  };

  editPaymentLink = data => {
    if (data.partial_payment) {
      this.trackPaymentLinkDetailsView('pl.update.partial');
    }

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
              onCloseClick: () => {
                this.trackPaymentLinkDetailsView(
                  `pl.update.reminder_enable.success`,
                  {
                    close: 1,
                  }
                );
              },
              onTimeOutClose: () => {
                this.trackPaymentLinkDetailsView(
                  `pl.update.reminder_enable.success`,
                  {
                    close: 0,
                  }
                );
              },
            });

            this.props.fetchInvoice(this.props.id);
          } else {
            this.props.showNotification({
              type: 'success',
              message: `${keysToSentence(d)} updated successfully`,
              onCloseClick: () => {
                Object.keys(data).forEach(key => {
                  this.trackPaymentLinkDetailsView(`pl.update.${key}.success`, {
                    close: 1,
                  });
                });
              },
              onTimeOutClose: () => {
                Object.keys(data).forEach(key => {
                  this.trackPaymentLinkDetailsView(`pl.update.${key}.success`, {
                    close: 0,
                  });
                });
              },
            });
          }

          if (
            d.hasOwnProperty('expire_by') &&
            this.props.user.isRemindersEnabled
          ) {
            this.fetchDataForInvoice();
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
      <Details
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
        isPaymentLinksRemindersEnabled={
          this.state.isPaymentLinksRemindersEnabled
        }
        trackEditReceipt={this.trackEditReceipt}
        trackEditExpiry={this.trackEditExpiry}
        trackEditNotes={this.trackEditNotes}
      />
    );
  }
}
