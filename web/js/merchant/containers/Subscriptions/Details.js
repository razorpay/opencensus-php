import React from 'react';
import PropTypes from 'prop-types';
import { findDOMNode } from 'react-dom';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import AddOnCreation from 'merchant/containers/AddOns/New';

import SubscriptionDetails from 'merchant/components/Subscriptions/Details';
import InvoiceDetail from 'merchant/components/Subscriptions/InvoiceDetail';
import CreditNoteDetails from 'merchant/components/Invoices/CreditNoteDetails';

import Plan from 'merchant/models/Plan';

import { fetchPlan } from 'merchant/modules/plans';
import { deleteAddOn } from 'merchant/modules/addons';
import { fetchCustomer } from 'merchant/modules/customers';
import { openModal, closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { fetchInvoice } from 'merchant/modules/invoices/details';
import { fetchSubscriptionAddOns } from 'merchant/modules/addons';
import { expandSlider, compactSlider } from 'rzp/modules/slider';
import {
  fetchInvoices,
  fetchCreditNote,
  paymentManualAttempt,
  fetchScheduledChanges,
  cancelUpdateSubscription,
  fetchSubscription as fetchItem,
} from 'merchant/modules/subscriptions';

import fetchKeysAndCheckout from 'merchant/utils/fetchKeysAndCheckout';
import { getEventCategoryFromPath } from 'rzp/utils/rzp-utils';

import CancellationModal from './CancellationModal';
import TestPaymentModal from './TestPaymentModal';
/*
 * Invoice (Upfront?) |    Subscription(Start?)     | Type
 * --------------------------------------------------------------------
 *  Yes(has addon)    |  Immediate(start_at: null)  |  3
 *  Yes(has addon)    |  Future(start_at: future)   |  2
 *      No            |  Immediate(start_at: null)  |  1
 *      No            |  Future(start_at: future)   |  0
 * --------------------------------------------------------------------
 * */

@withRouter
@connect(
  state => ({
    ...state.session,
    ...state.subscription,
    ...state.app,
  }),
  {
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
  }
)
export default class SubscriptionDetailsContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super();

    this.state = {
      creditNotes: [],
      scheduledChanges: {
        isLoading: false,
        data: null,
        plan: null,
      },
    };
  }

  componentDidMount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Open Details - Subscriptions',
        eventLabel: `subscription_id=${id}`,
      });
  }

  componentWillUnmount() {
    const { closeUrl, id } = this.props,
      eventCategory = getEventCategoryFromPath(closeUrl);
    eventCategory &&
      window.rzpAnalytics({
        eventCategory: eventCategory,
        eventAction: 'Close Details - Subscriptions',
        eventLabel: `subscription_id=${id}`,
      });
  }

  componentWillMount() {
    this.props.id && this.fetchSubscriptionDetails(this.props.id);
    this.checkSecView(); // Reset view
    this.props.invoice_id && this.fetchInvoice(this.props.invoice_id);

    // fetching key and checkout js
    fetchKeysAndCheckout(
      this.props.user.current,
      key => {
        this.key = key;
      },
      error => {
        this.props.showNotification({
          type: 'error',
          message: error.errors,
        });
      }
    );
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchSubscriptionDetails(nextProps.id);
    }

    if (
      nextProps.invoice_id &&
      (!this.props.invoice_id || this.props.invoice_id !== nextProps.invoice_id)
    ) {
      this.fetchInvoice(nextProps.invoice_id);
    }

    this.checkSecView(nextProps.invoice_id);
  }

  checkSecView(invoiceId) {
    if (!invoiceId) {
      // this.setState({secView: false});
      this.props.compactSlider();

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
  }

  // Fetch invoice details
  fetchInvoice(id) {
    let { invoice } = this.props;

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
        .then(invoice => {
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
      promise.push(fetchCreditNote(subscriptionId));
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
    return fetchSubscriptionAddOns(subscriptionId).then(response => {
      this.setState({
        addons: response.data.items,
      });

      return response.data; // To success the chain of Promise.all
    });
  }

  fetchScheduledChanges = id => {
    return fetchScheduledChanges(id)
      .then(res => {
        this.setState({
          scheduledChanges: {
            ...this.state.scheduledChanges,
            data: res,
          },
        });

        const plan = new Plan();
        return plan.fetch(res.plan_id);
      })
      .then(resp => {
        this.setState({
          scheduledChanges: {
            ...this.state.scheduledChanges,
            plan: resp,
            isLoading: false,
          },
        });
      });
  };

  fetchSubscriptionDetails(id) {
    let { entity, fetchItem, fetchPlan, fetchCustomer } = this.props;

    this.setState({
      isLoading: true,
    });

    fetchItem(id)
      .then(subscription => {
        return Promise.all([
          fetchPlan(subscription.plan_id),
          subscription.customer_id && fetchCustomer(subscription.customer_id),
          this.fetchAddOns(subscription.id),
        ]).then(response => {
          this.setState(
            {
              isLoading: false,
              scheduledChanges: {
                ...this.state.scheduledChanges,
                isLoading: subscription.has_scheduled_changes,
              },
            },
            () => {
              if (subscription.has_scheduled_changes) {
                this.fetchScheduledChanges(id);
              }
            }
          );

          this.fetchInvoicesList(id, true);
        });
      })
      .catch(({ errors }) => {
        this.setState({ errors, isLoading: false });
      });
  }

  goToLink = type => (itemId, index) => {
    if (type === 'invoice') {
      this.setState({ curInvoiceIndex: index });

      this.props.history.push(
        `/subscriptions/${this.props.entity.id}/${itemId}`
      );

      if (this.invoiceView && findDOMNode(this.invoiceView)) {
        findDOMNode(this.invoiceView).classList.add('toggle-slider');
      }

      return;
    }

    this.props.history.push(`/subscriptions/${this.props.entity.id}/${itemId}`);

    if (this.creditNoteView && findDOMNode(this.creditNoteView)) {
      findDOMNode(this.creditNoteView).classList.add('toggle-slider');
    }

    return;
  };

  onCancellationModalMount = id => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Open Form - Cancel Subscription',
      eventLabel: `subscription_id=${id}`,
    });
  };

  onCancellationModalUnmount = id => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Close Form - Cancel Subscription',
      eventLabel: `subscription_id=${id}`,
    });
  };

  onSubscriptionCancel = (id, type) => {
    type =
      type === '1' ? 'cancel_at_end_of_billing_cycle' : 'cancel_immediately';
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Subscriptions',
      eventAction: 'Submit Form - Cancel Subscription',
      eventLabel: `cancel_option=${type}`,
    });
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
  };

  secClose = () => {
    let { compactSlider, history, location } = this.props;

    if (this.invoiceView) {
      findDOMNode(this.invoiceView).classList.toggle('toggle-slider');
    }

    compactSlider();

    // Going back to initial detail view mode. Remove the chunk in url after the last /.
    history.push(location.pathname.replace(/\/[^\/]+\/?$/, ''));
  };

  // Create FE only invoice for status next_due
  getUpcomingInvoiceDetails(chargeAt, planAmount, addOnsList = []) {
    // addOnsList to calculate the total amount for invoice
    const totalAddOnsAmount = addOnsList.reduce(
      (sum, addOn) => sum + addOn.quantity * addOn.item.amount,
      0
    );

    //TODO: Add addons list as well depending upon type in line_items (Will help in updating invoices list)
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
          .then(response => {
            window.rzpAnalytics({
              eventCategory: 'Dashboard - Subscriptions',
              eventAction: 'Submit Form - Charge Now',
              eventLabel: `subscription_id=${subscriptionId}`,
            });

            window.rzpAnalytics({
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
          .catch(err => {
            this.props.showNotification({
              type: 'error',
              message: err.errors,
            });
          });
      },
      onMount: () => {
        window.rzpAnalytics({
          eventCategory: 'Dashboard - Subscriptions',
          eventAction: 'Open Form - Charge Now',
          eventLabel: `subscription_id=${subscriptionId}`,
        });
      },
      abort: () => {
        window.rzpAnalytics({
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
    const { plan, user } = this.props;
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
      handler: status => {
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
      (['authenticated', 'active', 'halted', 'pending'].indexOf(subsStatus) >
        -1 ||
        (subsStatus === 'created' && (subsType === 0 || subsType === 2))) &&
      this.props.invoices.items.length < this.props.entity.total_count
    );
  }

  // Only next_due addons will have delete btn
  deleteAddOn = id => {
    this.context.confirm({
      message: 'Are you sure to delete this addon?', // TODO: Show name and id of Addon to be deleted
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        deleteAddOn(id)
          .then(response => {
            this.fetchAddOns(this.props.entity.id);

            this.props.showNotification({
              type: 'success',
              message: 'Add-on details successfully deleted',
            });
          })
          .catch(err => {
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
        <AddOnCreation
          addon={addon}
          subscriptionId={this.props.entity.id}
          onSave={this.handleOnCreateAddOn}
          closeModal={this.props.closeModal}
          currency={this.props.plan.item.currency}
        />
      ),
    });
  };

  handleCancelUpdateSubscription = id => () => {
    return cancelUpdateSubscription(id)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Updated subscription is canceled successfully',
        });

        this.setState({
          scheduledChanges: {
            data: null,
            plan: null,
            isLoading: false,
          },
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    let {
      plan,
      entity,
      customer,
      invoices,
      closeUrl,
      invoice_id,
      credit_note_id,
      activeSecEntityId,
    } = this.props;

    let {
      errors,
      invoice,
      secView,
      isLoading,
      creditNotes,
      invoiceErrors,
      invoiceLoading,
      scheduledChanges,
    } = this.state;

    let invoicesList = invoices;
    let invoiceSecView, creditNoteSecView;

    // Add 'next_due' invoice in the Invoices list
    if (!invoices.loading && !invoices.error && !scheduledChanges.loading) {
      const subscriptionData = scheduledChanges.data
          ? scheduledChanges.data
          : entity,
        planData = scheduledChanges.plan ? scheduledChanges.plan : plan;

      if (
        this.checkNextDueInvoiceValidity(
          subscriptionData.status,
          subscriptionData.type
        )
      ) {
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

        let nextDueInvoice = this.getUpcomingInvoiceDetails(
          chargeAt,
          planData.item ? planData.item.amount * subscriptionData.quantity : 0,
          this.state.addons
        );

        invoicesList.items.unshift(nextDueInvoice);
      }
    }

    // Secondary view : Invoice details
    if (secView === 'invoice') {
      let invoiceData = {};
      let isValidInvoice = true;
      if (
        this.props.invoice_id === 'inv_upcoming' &&
        Object.keys(entity).length && // Helps to simulate the loader for 'inv_upcoming' invoice
        !invoices.loading // To display upcoming invioce rightly
      ) {
        const subscriptionData = scheduledChanges.data
            ? scheduledChanges.data
            : entity,
          planData = scheduledChanges.plan ? scheduledChanges.plan : plan;

        // inv_upcoming exists only for these subscriptions status only
        if (
          this.checkNextDueInvoiceValidity(
            subscriptionData.status,
            subscriptionData.type
          )
        ) {
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
            this.state.addons
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
          invoiceData.line_items.forEach(item => {
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

      let plan = plan,
        subscription = entity,
        isInvoiceLoading = invoiceLoading;

      if (this.props.invoice_id === 'inv_upcoming' && invoiceData) {
        isInvoiceLoading = false;
      }

      if (this.props.invoice_id === 'inv_upcoming' && scheduledChanges.data) {
        subscription = scheduledChanges.data;
        plan = scheduledChanges.plan;
      }

      // If request is for /inv_upcoming then invoiceData will exist only if it's validInvoice.
      // And in this case InvoiceDetails won't show loader but error message
      invoiceSecView = (
        <InvoiceDetail
          plan={plan}
          addons={addonsList}
          invoice={invoiceData}
          mode={this.props.mode}
          onClose={this.secClose}
          subscription={subscription}
          isLoading={isInvoiceLoading}
          nextChargeAt={entity.charge_at}
          subscriptionId={this.props.id}
          isValidInvoice={isValidInvoice}
          onAddOnDelete={this.deleteAddOn}
          showAddOnModal={this.showAddOnModal}
          onManualAttempt={this.onManualAttempt}
          ref={comp => (this.invoiceView = comp)}
          statusMsg={makeErrorStatus(invoiceErrors)}
          curInvoiceIndex={this.state.curInvoiceIndex}
        />
      );
    }

    creditNoteSecView = (
      <CreditNoteDetails ref={comp => (this.creditNoteView = comp)} />
    );

    return (
      <div class="multi-content">
        <SubscriptionDetails
          plan={plan}
          subscription={entity}
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
            (entity.status === 'created'
              ? this.onTestChargeAttemptWhileCreate
              : this.onTestChargeAttempt)
          }
          customer={entity && entity.customer_id ? customer : {}}
          cancelUpdateSubscription={this.handleCancelUpdateSubscription}
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
      message: message,
    };
  }

  return {};
}
