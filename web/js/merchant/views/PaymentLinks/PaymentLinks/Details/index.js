import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import {
  fetchPLRemindersList,
  fetchPaymentLinkDetails,
  notifyCustomer,
  cancelPaymentLink,
} from 'merchant/reducers/paymentlinks/details';

import { updatePLInReduxList } from 'merchant/reducers/paymentlinks/list';

import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import Details from './Details';
import IssueConfirmModal from 'merchant/views/Invoices/Invoices/components/IssueConfirmModal';
import { editPaymentLink } from 'merchant/views/PaymentLinks/PaymentLinks/model';
import { keysToSentence, findBy } from 'common/utils/rzp-utils';
import { fetchReminders, fetchRemindersMerchantConfigs } from 'merchant/reducers/reminders';

import track from 'merchant/views/PaymentLinks/PaymentLinks/Details/track';
import { MIN_AMOUNT_TEXT } from 'merchant/views/PaymentLinks/PaymentLinks/components/Edit/EditMinimumAmount';
import { showNoExpiryPL } from 'merchant/views/PaymentLinks/utils';
import { compose } from 'redux';
const showNoExpiry = showNoExpiryPL();

class PaymentLinkDetails extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor() {
    super();
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

  UNSAFE_componentWillMount() {
    this.fetchDataForPaymentLink();
  }

  componentDidMount() {
    track.init(this.props.tracking.trackEvent);
    track.onDetailsView(this.getPaymentLinkType());
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchDataForPaymentLink(nextProps.id);
    }
  }

  fetchDataForPaymentLink = (id = this.props.id) => {
    this.props.fetchPaymentLinkDetails(id);
    this.fetchPLRemindersList(id);
  };

  fetchPLRemindersList = (id = this.props.id) => {
    const promiseList = [];

    if (!this.props.reminders.reminders.items.length) {
      promiseList.push(this.props.fetchReminders());
    } else {
      promiseList.push(Promise.resolve());
    }

    promiseList.push(fetchPLRemindersList(id));

    Promise.all(promiseList).then((respList) => {
      const namespace = this.props.user.isPaymentlinksV2Enabled
        ? 'payment_link_v2'
        : 'payment_link';

      const paymentLinksRemindersSettings =
        findBy(this.props.reminders.reminders.items, 'namespace', namespace) || {};

      this.setState({
        isPaymentLinksRemindersEnabled: paymentLinksRemindersSettings.active,
        isAutoRemindersUpdating: false,
        nextReminders: respList[1].data.next_run_at || [],
      });
    });
  };

  onChangeSendAutoReminder = (event) => {
    this.setState({
      isAutoRemindersUpdating: true,
    });

    this.editPaymentLink({
      reminder_enable: event.target.value === '1',
    }).then((resp) => {
      this.fetchPLRemindersList();
      return resp;
    });
    track.updateReminderTick(this.getPaymentLinkType());
  };

  notifyCustomer = (props, notifyProps) => {
    const promises = [];
    if (notifyProps.email_notify) {
      promises.push(this.props.notifyCustomer(props, 'email'));
    }
    if (notifyProps.sms_notify) {
      promises.push(this.props.notifyCustomer(props, 'sms'));
    }

    return Promise.all(promises)
      .then(() => {
        track.resendIssue();
        this.props.showNotification({
          type: 'success',
          message: 'Link sent successfully!',
          onCloseClick: () => {
            track.resendSuccess(1);
          },
          onTimeOutClose: () => {
            track.resendSuccess(0);
          },
        });
      })
      .catch((error) => {
        this.setState({
          statusMsg: {
            type: 'error',
            message: error.errors,
          },
        });
      });
  };

  showNotifyCustomerModal = () => {
    const customer = this.props.paymentlink.customer_details;
    if (!customer.customer_contact && !customer.customer_email) {
      this.props.showNotification({
        type: 'error',
        message: "Customer's contact/email was not provided",
      });
      return;
    }

    track.resendStart(this.getPaymentLinkType());

    this.props.openModal({
      size: 'small',
      component: (
        <IssueConfirmModal
          isPaymentLink
          customer={this.props.paymentlink.customer_details}
          onIssue={(notifyProps) => {
            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Send - Payment Link',
              eventLabel: `payment_link_id=${this.props.paymentlink.id}`,
            });
            return this.notifyCustomer(this.props.paymentlink, notifyProps);
          }}
          onMount={() => {
            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Open Form - Send Link',
              eventLabel: `payment_link_id=${this.props.paymentlink.id}`,
            });
          }}
          onUnmount={() => {
            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Close Form - Send Link',
              eventLabel: `payment_link_id=${this.props.paymentlink.id}`,
            });
          }}
          onCloseClick={() => {
            track.resendClose(this.getPaymentLinkType());
          }}
          onFieldChange={(event) => {
            track.notifyLink(this.getPaymentLinkType(), event.target.name);
          }}
        />
      ),
    });
  };

  getPaymentLinkType = () => {
    const paymentlink = this.props.paymentlink;
    return paymentlink.upi_link ? 'upi_pl' : 'standard';
  };

  cancelPaymentLink = () => {
    const paymentlink = this.props.paymentlink;
    const type = this.getPaymentLinkType();
    track.onDeactivate(type);

    this.context.confirm({
      header: 'Cancel Link?',
      message: () => (
        <div className="text-semi-muted">
          <p>The Link will be cancelled and the customer will not be able to pay for it.</p>
        </div>
      ),
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, don't!",
      action: () => {
        return this.props
          .cancelPaymentLink(paymentlink)
          .then((paymentlinkStatus) => {
            this.props.updatePLInReduxList({ data: paymentlinkStatus }, false);
            track.onDeactivateConfirm(type);
            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Submit Form - Cancel Payment Link',
              eventLabel: `payment_link_id=${paymentlinkStatus.id}`,
            });

            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Payment Links',
              eventAction: 'Close Form - Cancel Payment Link',
              eventLabel: `payment_link_id=${paymentlinkStatus.id}`,
            });

            this.props.showNotification({
              type: 'success',
              message: 'Link cancelled!',
              onCloseClick: () => {
                track.onDeactivateSuccess(this.getPaymentLinkType(), 1);
              },
              onTimeOutClose: () => {
                track.onDeactivateSuccess(this.getPaymentLinkType(), 0);
              },
            });
          })
          .catch(({ errors }) => {
            if (!errors || (errors instanceof Array === true && (!errors.length || !errors[0]))) {
              errors = 'Some network error has occurred';
            }

            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
      onMount: () => {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Payment Links',
          eventAction: 'Open Form - Cancel Payment Link',
          eventLabel: `payment_link_id=${paymentlink.id}`,
        });
      },
      abort: () => {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Payment Links',
          eventAction: 'Close Form - Cancel Payment Link',
          eventLabel: `payment_link_id=${paymentlink.id}`,
        });
        track.onDeactivateAbort(type);
      },
    });
  };

  trackEditReceipt = (changeType, type, modified) => {
    if (changeType === 'Edit Receipt') return;
    const eventName = changeType ? 'receipt_abort' : 'receipt_confirm';
    track.onUpdateReciept(eventName, this.getPaymentLinkType(), modified);
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
    track.paymentUpdateDetail(`pl.update.${trackEvent}`, { modified });
  };

  trackEditNotes = (changeType, modified) => {
    if (changeType === 'Save Notes') {
      track.updateNotes(this.getPaymentLinkType(), modified);
    }

    if (changeType === 'Delete Notes (Confirmed)') {
      track.updateNotesClosed(this.getPaymentLinkType(), modified);
    }

    if (changeType === 'Delete Notes (Cancelled)') {
      track.updateNotesClose(`pl.update.notes.close`);
    }
  };

  editPaymentLink = (data) => {
    const { showNotification } = this.props;
    // if expire by is mandatory then expire by should not be 'undefined'
    if (!showNoExpiry && !data?.expire_by && data?.hasOwnProperty('expire_by')) {
      showNotification({
        type: 'error',
        message: 'Expire By is mandatory!',
      });
      return false;
    }
    if (data.partial_payment) {
      track.onUpdatePartial(this.getPaymentLinkType());
    }

    return editPaymentLink(this.props.paymentlink.id, data)
      .then((resp) => {
        if (resp.data) {
          this.props.updatePLInReduxList(resp, false);

          let d = data;
          if (d.hasOwnProperty('first_payment_min_amount')) {
            d = { ...data };

            d[MIN_AMOUNT_TEXT.split(' ').join('_')] = d.first_payment_min_amount;
            delete d.first_payment_min_amount;
          }

          if (d.hasOwnProperty('reminder_enable')) {
            this.props.showNotification({
              type: 'success',
              message: `Auto reminders has been ${
                d.reminder_enable ? 'enabled' : 'disabled'
              } successfully`,
              onCloseClick: () => {
                track.updateReminderEnable(`pl.update.reminder_enable.success`, {
                  close: 1,
                });
              },
              onTimeOutClose: () => {
                track.updateReminderEnable(`pl.update.reminder_enable.success`, {
                  close: 0,
                });
              },
            });

            this.props.fetchPaymentLinkDetails(this.props.id);
          } else {
            this.props.showNotification({
              type: 'success',
              message: `${keysToSentence(d)} updated successfully`,
              onCloseClick: () => {
                Object.keys(data).forEach((key) => {
                  track.paymentLinkDetailsUpdateView(`pl.update.${key}.success`, {
                    close: 1,
                  });
                });
              },
              onTimeOutClose: () => {
                Object.keys(data).forEach((key) => {
                  track.paymentLinkDetailsUpdateView(`pl.update.${key}.success`, {
                    close: 0,
                  });
                });
              },
            });
          }

          if (d.hasOwnProperty('expire_by')) {
            this.fetchDataForPaymentLink();
          }

          return resp;
        } else {
          throw new Error('Some Network error occured');
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          errors.forEach((e) => {
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
    const { loading, paymentlink, user } = this.props;
    const statusMsg = this.state.statusMsg;
    return (
      <Details
        user={user}
        paymentlink={paymentlink}
        isLoading={loading}
        statusMsg={statusMsg}
        nextReminders={this.state.nextReminders}
        notifyCustomer={this.showNotifyCustomerModal}
        onCancel={this.cancelPaymentLink}
        editPaymentLink={this.editPaymentLink}
        isRoleAllowedEdit={user.isAllowedEdit('payment_links')}
        onChangeSendAutoReminder={this.onChangeSendAutoReminder}
        isAutoRemindersUpdating={this.state.isAutoRemindersUpdating}
        isMinimumFirstPaymentEnabled={user.isMinimumFirstPaymentEnabled}
        isPaymentLinksRemindersEnabled={this.state.isPaymentLinksRemindersEnabled}
        trackEditReceipt={this.trackEditReceipt}
        trackEditExpiry={this.trackEditExpiry}
        trackEditNotes={this.trackEditNotes}
        showNoExpiry={showNoExpiry}
      />
    );
  }
}

export default compose(
  connect(
    (state) => ({
      ...state.paymentlink,
      ...state.session,
      reminders: state.reminders,
    }),
    {
      ...ModalActions,
      ...NotificationsActions,
      fetchPaymentLinkDetails,
      notifyCustomer,
      updatePLInReduxList,
      cancelPaymentLink,
      fetchReminders,
      fetchRemindersMerchantConfigs,
    },
  ),
  rTracking(() => window.rzpQ.component('InvoiceDetailContainer')),
)(PaymentLinkDetails);
