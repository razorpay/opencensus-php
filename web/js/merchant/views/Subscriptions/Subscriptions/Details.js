// eslintdisabled for this file in .eslintignore
import React from 'react';
import PropTypes from 'prop-types';
import { findDOMNode } from 'react-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { withRouter } from 'common/deprecated/withRouter';
import { getEventCategoryFromPath } from 'common/utils/rzp-utils';
import Plan from 'merchant/models/Plan';
import { withI18Service } from 'common/i18';

import { deleteAddOn, fetchSubscriptionAddOns } from 'merchant/reducers/addons';
import { fetchCustomer } from 'merchant/reducers/customers';
import { fetchInvoice, fetchCreditNote } from 'merchant/reducers/invoices/details';
import { fetchOffer } from 'merchant/reducers/offers/offerDetails';
import { fetchPlan } from 'merchant/reducers/plans';
import {
  fetchInvoices,
  paymentManualAttempt,
  fetchScheduledChanges,
  cancelUpdateSubscription,
  fetchSubscriptionCreditNotes,
  fetchSubscription as fetchItem,
  pauseAndResumeSubscription,
  removeOffersOnSubscription,
} from 'merchant/reducers/subscriptions';
import fetchKeysAndCheckout from 'merchant/utils/fetchKeysAndCheckout';
import CreateAddOnModal from 'merchant/views/Subscriptions/Subscriptions/components/AddOnsModal';
import CreditNoteDetails from 'merchant/views/Subscriptions/Subscriptions/components/CreditNoteDetails';
import SubscriptionDetails from 'merchant/views/Subscriptions/Subscriptions/components/Details';
import InvoiceDetail from 'merchant/views/Subscriptions/Subscriptions/components/InvoiceDetail';
import analytics from 'merchant/views/Subscriptions/analytics';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { expandSlider, compactSlider } from 'merchant_common/reducers/slider';

import CancellationModal from './components/CancellationModal';
import TestPaymentModal from './components/TestPaymentModal';
/*
 * Invoice (Upfront?) |    Subscription(Start?)     | Type
 * --------------------------------------------------------------------
 *  Yes(has addon)    |  Immediate(start_at: null)  |  3
 *  Yes(has addon)    |  Future(start_at: future)   |  2
 *      No            |  Immediate(start_at: null)  |  1
 *      No            |  Future(start_at: future)   |  0
 * --------------------------------------------------------------------
 * */

const scheduledChangesInitValue = {
  data: null,
  plan: {
    item: {},
  },
  isLoading: false,
};

@connect(
  (state) => ({
    ...state.session,
    ...state.subscription,
    ...state.app,
    offer: state.offer,
  }),
  {
    fetchOffer,
    fetchPlan,
    fetchItem,
    openModal,
    closeModal,
    fetchInvoice,
    expandSlider,
    compactSlider,
    fetchInvoices,
    fetchCustomer,
    showNotification,
    removeOffersOnSubscription,
    pauseAndResumeSubscription,
  },
)
@RTracking(() => window.rzpQ.component('SubscriptionDetailsContainer'))
class SubscriptionDetailsContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor() {
    super();

    this.state = {
      creditNotes: [],
      creditNote: {
        data: {},
        isLoading: false,
        statusMsg: {},
      },
      scheduledChanges: scheduledChangesInitValue,
      selectedOffer: {},
    };
  }

  componentDidMount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Open Details - Subscriptions',
        eventLabel: `subscription_id=${id}`,
      });

    this.props.tracking.trackEvent(
      window.rzpQ.subscription().interaction('subscription.click.subscription_id'),
    );
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Close Details - Subscriptions',
        eventLabel: `subscription_id=${id}`,
      });
  }

  UNSAFE_componentWillMount() {
    this.props.id && this.fetchSubscriptionDetails(this.props.id);
    this.checkSecView(); // Reset view
    this.props.invoice_id && this.fetchInvoice(this.props.invoice_id);

    // fetching key and checkout js
    fetchKeysAndCheckout(
      this.props.user.current,
      (key) => {
        this.key = key;
      },
      () => {},
    );
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchSubscriptionDetails(nextProps.id);
    }

    if (
      nextProps.invoice_id &&
      (!this.props.invoice_id || this.props.invoice_id !== nextProps.invoice_id)
    ) {
      this.fetchInvoice(nextProps.invoice_id);
    }

    if (nextProps.credit_note_id && nextProps.credit_note_id !== this.props.credit_note_id) {
      this.fetchCreditNote(nextProps.credit_note_id);
    }

    this.checkSecView(nextProps.invoice_id, nextProps.credit_note_id);
  }

  checkSecView(invoiceId, creditNoteId) {
    if (!creditNoteId) {
      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.creditNoteView && findDOMNode(this.creditNoteView)) {
        findDOMNode(this.creditNoteView).classList.add('toggle-slider');
      }

      this.setState({
        creditNote: {
          data: {},
          isLoading: false,
          statusMsg: {},
        },
      });
    } else if (this.creditNoteView && findDOMNode(this.creditNoteView)) {
      // If already opened then close it
      findDOMNode(this.creditNoteView).classList.remove('toggle-slider');
    }

    if (!invoiceId) {
      // To avoid not toggling issue when browser back btn is clicked when secondary view is overlayed in dual view while small-screen
      if (this.invoiceView && findDOMNode(this.invoiceView)) {
        findDOMNode(this.invoiceView).classList.add('toggle-slider');
      }

      this.setState({
        invoice: {},
        invoiceLoading: false,
      });
    } else if (this.invoiceView && findDOMNode(this.invoiceView)) {
      // If already opened then close it
      findDOMNode(this.invoiceView).classList.remove('toggle-slider');
    }

    if (!invoiceId && !creditNoteId) {
      this.props.compactSlider();
    }
  }

  fetchCreditNote = (id) => {
    this.props.expandSlider();

    this.setState({
      secView: 'credit_note',
      creditNote: {
        data: {},
        statusMsg: {},
        isLoading: true,
      },
    });

    fetchCreditNote(id)
      .then((resp) => {
        this.setState({
          creditNote: {
            statusMsg: {},
            data: resp.data,
            isLoading: false,
          },
        });
      })
      .catch(({ errors }) => {
        this.setState((prevState) => ({
          creditNote: {
            ...prevState.creditNote,
            statusMsg: {
              type: 'error',
              message: errors,
            },
            isLoading: false,
          },
        }));
      });
  };

  // Fetch invoice details
  fetchInvoice(id) {
    this.props.expandSlider();

    this.setState({
      secView: 'invoice',
      invoiceErrors: null,
      invoiceLoading: true,
    });

    // invoice with next_due(inv_upcoming) will be auto created in render fn.
    if (id !== 'inv_upcoming') {
      this.props
        .fetchInvoice(id)
        .then((invoice) => {
          this.setState({
            invoice,
            invoiceLoading: false,
          });
        })
        .catch(({ invoiceErrors }) => {
          this.setState({
            invoiceErrors,
          });
        });
    }
  }

  // Fetch list of invoices for subscriptions id
  fetchInvoicesList(subscriptionId, isCreditNoteAvl = false) {
    const promise = [this.props.fetchInvoices(subscriptionId)];

    if (isCreditNoteAvl) {
      promise.push(fetchSubscriptionCreditNotes(subscriptionId));
    }

    return Promise.all(promise).then(([data, creditNotes]) => {
      if (data.data && !this.state.curInvoiceIndex) {
        const invoicesItems = data.data.items;

        // Calculate recurring id #
        if (this.props.invoice_id === 'inv_upcoming') {
          this.setState({
            curInvoiceIndex: invoicesItems.length + 1,
          });
        } else {
          // Iterate list to find which invoice id is matching url(props)
          invoicesItems.forEach((item, index) => {
            if (item.id === this.props.invoice_id) {
              this.setState({
                curInvoiceIndex: invoicesItems.length - index,
              });
            }
          });
        }
      }

      if (creditNotes && creditNotes.data.items) {
        this.setState({
          creditNotes: creditNotes.data.items,
        });
      }
    });
  }

  fetchAddOns(subscriptionId) {
    return fetchSubscriptionAddOns(subscriptionId).then((response) => {
      this.setState({
        addons: response.data.items,
      });

      return response.data; // To success the chain of Promise.all
    });
  }

  fetchScheduledChanges = (id) => {
    return fetchScheduledChanges(id)
      .then((res) => {
        this.setState((prevState) => ({
          scheduledChanges: {
            ...prevState.scheduledChanges,
            data: res,
          },
        }));

        const plan = new Plan();
        return plan.fetch(res.plan_id);
      })
      .then((resp) => {
        this.setState((prevState) => ({
          scheduledChanges: {
            ...prevState.scheduledChanges,
            plan: resp,
            isLoading: false,
          },
        }));
      });
  };

  fetchSubscriptionDetails(id) {
    this.setState({
      isLoading: true,
    });

    this.props
      .fetchItem(id)
      .then((subscription) => {
        return Promise.all([
          this.props.fetchPlan(subscription.plan_id),
          subscription.customer_id && this.props.fetchCustomer(subscription.customer_id),
          this.fetchAddOns(subscription.id),
          subscription.offer_id && this.props.fetchOffer(subscription.offer_id),
        ]).then((response) => {
          this.setState(
            {
              isLoading: false,
              scheduledChanges: {
                ...scheduledChangesInitValue,
                isLoading: subscription.has_scheduled_changes,
              },
            },
            () => {
              if (subscription.has_scheduled_changes) {
                this.fetchScheduledChanges(id);
              }
            },
          );

          this.fetchInvoicesList(id, true);

          this.setState({
            selectedOffer: response[3],
          });
        });
      })
      .catch(({ errors }) => {
        this.setState({ errors, isLoading: false });
      });
  }

  goToLink = (type) => (itemId, index) => {
    if (type === 'invoice') {
      this.setState({ curInvoiceIndex: index });

      this.props.history.push(`/subscriptions/${this.props.entity.id}/${itemId}`);

      if (this.invoiceView && findDOMNode(this.invoiceView)) {
        findDOMNode(this.invoiceView).classList.add('toggle-slider');
      }

      return;
    }

    this.props.history.push(`/subscriptions/${this.props.entity.id}/${itemId}`);

    if (this.creditNoteView && findDOMNode(this.creditNoteView)) {
      findDOMNode(this.creditNoteView).classList.add('toggle-slider');
    }
  };

  onCancellationModalMount = (id) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Open Form - Cancel Subscription',
      eventLabel: `subscription_id=${id}`,
    });
  };

  onCancellationModalUnmount = (id) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Close Form - Cancel Subscription',
      eventLabel: `subscription_id=${id}`,
    });
  };

  onSubscriptionCancel = (id, type) => {
    type = type === '1' ? 'cancel_at_end_of_billing_cycle' : 'cancel_immediately';
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Submit Form - Cancel Subscription',
      eventLabel: `cancel_option=${type}`,
    });

    if (type === '1') {
      analytics.track('subscription.cancel.end_of_cycle');
    } else {
      analytics.track('subscription.cancel.immediate');
    }
    analytics.track('subscription.cancel.confirm');
  };

  cancelSubscription = () => {
    this.props.openModal({
      component: (
        <CancellationModal
          subscriptionId={this.props.id}
          onMount={this.onCancellationModalMount}
          onUnmount={this.onCancellationModalUnmount}
          onSubscriptionCancel={this.onSubscriptionCancel}
        />
      ),
      size: 'small',
    });
    analytics.track('subscription.cancel.initiate');
  };

  secClose = () => {
    const { history, location } = this.props;

    if (this.invoiceView) {
      findDOMNode(this.invoiceView).classList.toggle('toggle-slider');
    }

    if (this.creditNoteView) {
      findDOMNode(this.creditNoteView).classList.toggle('toggle-slider');
    }

    this.props.compactSlider();

    // Going back to initial detail view mode. Remove the chunk in url after the last /.
    //eslint-disable-next-line
    history.push(location.pathname.replace(/\/[^\/]+\/?$/, ''));
  };

  // Create FE only invoice for status next_due
  getUpcomingInvoiceDetails(chargeAt, planAmount, addOnsList = []) {
    if (this.state.scheduledChanges.plan.id) {
      return null;
    }

    // addOnsList to calculate the total amount for invoice
    const totalAddOnsAmount = addOnsList.reduce(
      (sum, addOn) => sum + addOn.quantity * addOn.item.amount,
      0,
    );

    // TODO: Add addons list as well depending upon type in line_items (Will help in updating invoices list)
    return {
      id: 'inv_upcoming',
      status: 'next_due',
      currency: this.props.plan && this.props.plan.item.currency,
      billing_start: chargeAt,
      amount: planAmount + totalAddOnsAmount,
    };
  }

  // Manual Attempt to invoice charge
  onManualAttempt = (invoiceId, subscriptionId = this.props.id) => {
    this.context.confirm({
      header: 'Are you sure you want to manually charge it?',
      message: null,
      affirmativeLabel: 'Yes',
      affirmativePendingLabel: 'Charging...',
      abortLabel: "No, don't!",
      action: () => {
        return paymentManualAttempt(subscriptionId, invoiceId)
          .then((response) => {
            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Subscriptions',
              eventAction: 'Submit Form - Charge Now',
              eventLabel: `subscription_id=${subscriptionId}`,
            });

            window.rzpAnalytics?.({
              eventCategory: 'Dashboard - Subscriptions',
              eventAction: 'Close Form - Charge Now',
              eventLabel: `subscription_id=${subscriptionId}`,
            });

            // Show success notification
            this.props.showNotification({
              type: 'success',
              message: 'Manual charge attempt is successful',
            });

            this.props.closeModal();

            // Make all fetch calls
            // this.fetchSubscriptionDetails(this.props.entity.id);
            // this.fetchInvoicesList(this.props.entity.id);
            this.postChargeAttempt();

            return response;
          })
          .catch((err) => {
            this.props.showNotification({
              type: 'error',
              message: err.errors,
            });
          });
      },
      onMount: () => {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Subscriptions',
          eventAction: 'Open Form - Charge Now',
          eventLabel: `subscription_id=${subscriptionId}`,
        });
      },
      abort: () => {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Subscriptions',
          eventAction: 'Close Form - Charge Now',
          eventLabel: `subscription_id=${subscriptionId}`,
        });
      },
    });
  };

  // Refetch the details after success of test btn charge attempt / manual charge attemp
  postChargeAttempt = () => {
    // Make all fetch calls
    this.fetchSubscriptionDetails(this.props.entity.id);
    this.fetchInvoicesList(this.props.entity.id);

    if (this.state.invoice && this.state.invoice.id) {
      this.fetchInvoice(this.state.invoice.id);
    }
  };

  // Modal for Testing Button
  onTestChargeAttempt = () => {
    this.props.openModal({
      component: (
        <TestPaymentModal
          subscriptionId={this.props.id}
          subscriptionStatus={this.props.entity.status}
          postAction={this.postChargeAttempt}
        />
      ),
      size: 'small',
    });
  };

  // testing charge while subscription status is created
  onTestChargeAttemptWhileCreate = () => {
    const { user } = this.props;
    const razorpay = new window.Razorpay({
      key: this.key,
      description: 'Start Subscription',
      prefill: {
        name: user.name,
        email: user.email,
        contact: user.contact_mobile,
      },
      notes: {
        dashboard: true,
      },
      subscription_id: this.props.entity.id,
      handler: () => {
        this.postChargeAttempt();
      },
    });

    try {
      razorpay.open();
    } catch (e) {
      this.props.showNotification({
        type: 'error',
        message: e.errors,
      });
    }
  };

  // Check if next due invoice is valid for current subscription
  checkNextDueInvoiceValidity(subsStatus, subsType) {
    return (
      (['authenticated', 'active', 'halted', 'pending'].indexOf(subsStatus) > -1 ||
        (subsStatus === 'created' && (subsType === 0 || subsType === 2))) &&
      this.props.invoices.items.length < this.props.entity.total_count
    );
  }

  // Only next_due addons will have delete btn
  deleteAddOn = (id) => {
    this.context.confirm({
      message: 'Are you sure to delete this addon?', // TODO: Show name and id of Addon to be deleted
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        deleteAddOn(id)
          .then(() => {
            this.fetchAddOns(this.props.entity.id);

            this.props.showNotification({
              type: 'success',
              message: 'Add-on details successfully deleted',
            });
          })
          .catch((err) => {
            this.setState({
              errors: err.errors,
            });
          }),
    });
  };

  handleOnCreateAddOn = () => {
    this.props.closeModal();

    this.fetchAddOns(this.props.entity.id);
  };

  showAddOnModal = (addon = null) => {
    this.props.openModal({
      size: 'small',
      component: (
        <CreateAddOnModal
          addon={addon}
          subscriptionId={this.props.entity.id}
          onSave={this.handleOnCreateAddOn}
          closeModal={this.props.closeModal}
          currency={this.props.plan.item.currency}
        />
      ),
    });
  };

  handleCancelUpdateSubscription = (id) => () => {
    return this.context.confirm({
      header: 'Cancel Update',
      message: 'Are you sure you want to cancel the update?',
      affirmativeLabel: 'Yes, cancel',
      affirmativePendingLabel: 'Canceling...',
      abortLabel: "No, don't!",
      action: () => {
        return cancelUpdateSubscription(id)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'Updated subscription is canceled successfully',
            });

            this.resetScheduledChanges();
          })
          .catch((err) => {
            this.props.showNotification({
              type: 'error',
              message: err.errors,
            });
          });
      },
    });
  };

  onClickPauseAndResume = () => {
    const { id, status } = this.props.entity;
    const isStatusPaused = status === 'paused';
    const type = isStatusPaused ? 'Resume' : 'Pause';
    const message = isStatusPaused
      ? 'Are you sure you want to resume the subscription?'
      : 'This subscription will not be charged till it is resumed. Are you sure you want to pause the subscription?';

    this.context.confirm({
      header: `${type} Subscription?`,
      message,
      affirmativeLabel: `Yes, ${type} Now`,
      affirmativePendingLabel: `${type}ing...`,
      abortLabel: 'No, Don’t!',
      action: () => {
        return this.props
          .pauseAndResumeSubscription({ id, status })
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: `Subscription is ${isStatusPaused ? 'resumed' : 'paused'} successfully`,
            });
          })
          .catch((err) => {
            this.props.showNotification({
              type: 'error',
              message: err.errors,
            });
          });
      },
    });
  };

  resetScheduledChanges = () => this.setState({ scheduledChanges: scheduledChangesInitValue });

  removeOffer = () => {
    return this.context.confirm({
      header: 'Remove Offer!',
      message: (
        <div>
          This offer is still being used to discount upcoming payment(s) of this subscription.
          <br />
          <br />
          Are you sure you want to remove this offer?
        </div>
      ),
      affirmativeLabel: 'Yes, Remove',
      affirmativePendingLabel: 'Removing...',
      abortLabel: "No, don't!",
      action: () => {
        return this.props
          .removeOffersOnSubscription(this.props.id, this.props.entity.offer_id)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'Offer is removed for this subscription successfully',
            });
          })
          .catch((err) => {
            this.props.showNotification({
              type: 'error',
              message: err.errors,
            });
          });
      },
    });
  };

  render() {
    const {
      user,
      plan,
      entity,
      customer,
      invoices,
      closeUrl,
      invoice_id,
      credit_note_id,
      activeSecEntityId,
    } = this.props;

    const {
      errors,
      invoice,
      secView,
      isLoading,
      creditNote,
      creditNotes,
      invoiceErrors,
      invoiceLoading,
      scheduledChanges,
      selectedOffer,
    } = this.state;

    let invoicesList = invoices;
    let invoiceSecView, creditNoteSecView;

    // Add 'next_due' invoice in the Invoices list
    if (!invoices.loading && !invoices.error) {
      const subscriptionData = scheduledChanges.data ? scheduledChanges.data : entity;
      const planData = scheduledChanges.plan.id ? scheduledChanges.plan : plan;

      if (this.checkNextDueInvoiceValidity(subscriptionData.status, subscriptionData.type)) {
        invoicesList = { ...invoices };
        invoicesList.items = [...invoices.items]; // To avoid multiple additions when render is called multiple times

        let chargeAt;
        chargeAt =
          subscriptionData.status === 'created' &&
          (subscriptionData.type === 0 || subscriptionData.type === 2)
            ? subscriptionData.charge_at
            : null;

        if (subscriptionData.status === 'pending') {
          chargeAt = null;
        }

        const nextDueInvoice = this.getUpcomingInvoiceDetails(
          chargeAt,
          planData.item ? planData.item.amount * subscriptionData.quantity : 0,
          this.state.addons,
        );

        nextDueInvoice && invoicesList.items.unshift(nextDueInvoice);
      }
    }

    // Secondary view : Invoice details
    if (secView === 'invoice') {
      let invoiceData = {};
      let isValidInvoice = true;
      if (
        this.props.invoice_id === 'inv_upcoming' &&
        Object.keys(entity).length && // Helps to simulate the loader for 'inv_upcoming' invoice
        !invoices.loading // To display upcoming invoice rightly
      ) {
        const subscriptionData = scheduledChanges.data ? scheduledChanges.data : entity;
        const planData = scheduledChanges.plan.id ? scheduledChanges.plan : plan;

        // inv_upcoming exists only for these subscriptions status only
        if (this.checkNextDueInvoiceValidity(subscriptionData.status, subscriptionData.type)) {
          // Charge at is not available in such type of subscriptions
          let chargeAt =
            subscriptionData.status === 'created' &&
            (subscriptionData.type === 0 || subscriptionData.type === 2)
              ? subscriptionData.charge_at
              : null;

          if (subscriptionData.status === 'pending') {
            chargeAt = null;
          }

          invoiceData = this.getUpcomingInvoiceDetails(
            chargeAt,
            planData.item.amount * subscriptionData.quantity,
            this.state.addons,
          );
        } else {
          isValidInvoice = false; // '/inv_upcoming' is invalid url for such subscriptions
        }
      } else {
        invoiceData = invoice;
      }

      let addonsList = [];
      if (invoiceData) {
        if (invoiceData.status === 'next_due' && this.state.addons) {
          addonsList = this.state.addons;
        } else if (invoiceData.line_items) {
          invoiceData.line_items.forEach((item) => {
            if (item.type === 'addon') {
              addonsList.push({
                quantity: item.quantity,
                item: {
                  ...item,
                },
              });
            }
          });
        }
      }

      let planDetails = plan;
      let subscriptionDetails = entity;
      let isInvoiceLoading = invoiceLoading;

      if (this.props.invoice_id === 'inv_upcoming' && invoiceData) {
        isInvoiceLoading = false;
      }

      if (this.props.invoice_id === 'inv_upcoming' && scheduledChanges.data) {
        subscriptionDetails = scheduledChanges.data;
        planDetails = scheduledChanges.plan;
      }

      // If request is for /inv_upcoming then invoiceData will exist only if it's validInvoice.
      // And in this case InvoiceDetails won't show loader but error message
      invoiceSecView = (
        <InvoiceDetail
          plan={planDetails}
          addons={addonsList}
          invoice={invoiceData}
          mode={this.props.mode}
          onClose={this.secClose}
          isLoading={isInvoiceLoading}
          nextChargeAt={subscriptionDetails.charge_at}
          subscriptionId={this.props.id}
          isValidInvoice={isValidInvoice}
          onAddOnDelete={this.deleteAddOn}
          subscription={subscriptionDetails}
          showAddOnModal={this.showAddOnModal}
          onManualAttempt={this.onManualAttempt}
          ref={(comp) => {
            this.invoiceView = comp;
          }}
          statusMsg={makeErrorStatus(invoiceErrors)}
          curInvoiceIndex={this.state.curInvoiceIndex}
          isSubscriptionOffersEnabled={
            user.isSubscriptionOffersEnabled &&
            !this.props.i18.isConfigTagEnabled('subscriptions.subscription_offers')
          }
        />
      );
    }

    if (secView === 'credit_note') {
      creditNoteSecView = (
        <CreditNoteDetails
          onClose={this.secClose}
          creditNote={creditNote.data}
          statusMsg={creditNote.statusMsg}
          isLoading={creditNote.isLoading}
          ref={(comp) => {
            this.creditNoteView = comp;
          }}
        />
      );
    }

    return (
      <div class="multi-content">
        <SubscriptionDetails
          plan={plan}
          subscription={entity}
          selectedOffer={selectedOffer}
          isSideView={closeUrl}
          isLoading={isLoading}
          mode={this.props.mode}
          invoices={invoicesList}
          goToLink={this.goToLink}
          creditNotes={creditNotes}
          statusMsg={makeErrorStatus(errors)}
          scheduledChanges={scheduledChanges}
          activeSecEntityId={activeSecEntityId}
          onCancelClick={this.cancelSubscription}
          onManualAttempt={this.onManualAttempt}
          onTestChargeAttempt={
            this.props.mode === 'test' &&
            (entity.status === 'created' && entity.payment_method !== 'emandate'
              ? this.onTestChargeAttemptWhileCreate
              : this.onTestChargeAttempt)
          }
          customer={entity && entity.customer_id ? customer : {}}
          cancelUpdateSubscription={this.handleCancelUpdateSubscription}
          onClickPauseAndResume={this.onClickPauseAndResume}
          isSubscriptionPauseAndResumeEnabled={user.isSubscriptionPauseAndResumeEnabled}
          isSubscriptionOffersEnabled={
            user.isSubscriptionOffersEnabled &&
            !this.props.i18.isConfigTagEnabled('subscriptions.subscription_offers')
          }
          removeOffer={this.removeOffer}
        />

        {invoice_id && invoiceSecView}

        {credit_note_id && creditNoteSecView}
      </div>
    );
  }
}

function makeErrorStatus(message) {
  if (message) {
    return {
      type: 'error',
      message,
    };
  }

  return {};
}

export default withRouter(withI18Service(SubscriptionDetailsContainer));
