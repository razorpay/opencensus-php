import React, { Component, PropTypes } from 'react';
import { findDOMNode } from 'react-dom';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import SubscriptionDetails from 'merchant/components/Subscriptions/Details';
import InvoiceDetail from 'merchant/components/Subscriptions/InvoiceDetail';
import {
  fetchSubscription as fetchItem,
  fetchInvoices,
  paymentManualAttempt,
} from 'merchant/modules/subscriptions';
import { fetchPlan } from 'merchant/modules/plans';
import { fetchCustomer } from 'merchant/modules/customers';
import { fetchInvoice } from 'merchant/modules/invoices/details';
import { fetchSubscriptionAddOns } from 'merchant/modules/addons';
import { deleteAddOn } from 'merchant/modules/addons';
import { showNotification } from 'rzp/modules/notifications';
import { expandSlider, compactSlider } from 'rzp/modules/slider';
import fetchKeysAndCheckout from 'merchant/utils/fetchKeysAndCheckout';

import { openModal, closeModal } from 'rzp/modules/modals';
import CancellationModal from './CancellationModal';
import TestPaymentModal from './TestPaymentModal';
import AddOnCreation from 'merchant/containers/AddOns/New';
import { getEventCategoryFromPath } from 'rzp/utils/rzp-utils';

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
  state => {
    return {
      ...state.session,
      ...state.subscription,
      ...state.app,
    };
  },
  {
    expandSlider,
    compactSlider,
    fetchInvoice,
    fetchItem,
    fetchInvoices,
    fetchPlan,
    fetchCustomer,
    showNotification,
    openModal,
    closeModal,
    ...AddFundsActions,
  }
)
export default class SubscriptionDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {};

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
        // do something for error
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
  fetchInvoicesList(subscriptionId) {
    return this.props.fetchInvoices(subscriptionId).then(data => {
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

  fetchSubscriptionDetails(id) {
    let { entity, fetchItem, fetchPlan, fetchCustomer } = this.props;

    this.setState({ isLoading: true });

    fetchItem(id)
      .then(subscription => {
        return Promise.all([
          fetchPlan(subscription.plan_id),
          fetchCustomer(subscription.customer_id),
          this.fetchAddOns(subscription.id),
        ]).then(response => {
          this.setState({ isLoading: false });
          this.fetchInvoicesList(id);
        });
      })
      .catch(({ errors }) => {
        this.setState({ errors, isLoading: false });
      });
  }

  goToLink = (itemId, index) => {
    this.setState({ curInvoiceIndex: index });
    this.props.history.push(`/subscriptions/${this.props.entity.id}/${itemId}`);

    if (this.invoiceView && findDOMNode(this.invoiceView)) {
      findDOMNode(this.invoiceView).classList.add('toggle-slider');
    }
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
    findDOMNode(this.invoiceView).classList.toggle('toggle-slider');

    compactSlider();
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
      currency: 'INR',
      billing_start: chargeAt,
      amount: planAmount + totalAddOnsAmount,
    };
  }

  // Manual Attempt to invoice charge
  onManualAttempt = (invoiceId, subscriptionId) => {
    this.context.confirm({
      header: 'Are you sure you want to manually charge it?',
      message: null,
      affirmativeLabel: 'Yes',
      affirmativePendingLabel: 'Charging...',
      abortLabel: "No, don't!",
      action: () => {
        return paymentManualAttempt(invoiceId)
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
      amount: this.props.plan.item.amount,
      prefill: {
        name: user.name,
        email: user.email,
        contact: user.contact_mobile,
      },
      notes: {
        dashboard: true,
      },
      handler: status => {
        this.postChargeAttempt();
      },
    });

    try {
      razorpay.open();
    } catch (e) {
      // do something
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
        />
      ),
    });
  };

  render() {
    let { entity, plan, customer, invoices, activeSecEntityId } = this.props;
    let {
      isLoading,
      invoice,
      secView,
      errors,
      invoiceErrors,
      invoiceLoading,
    } = this.state;

    let invoicesList = invoices;
    let invoiceSecView;

    // Add 'next_due' invoice in the Invoices list
    if (!invoices.loading && !invoices.error) {
      if (this.checkNextDueInvoiceValidity(entity.status, entity.type)) {
        invoicesList = { ...invoices };
        invoicesList.items = [...invoices.items]; // To avoid multiple additions when render is called multiple times

        let chargeAt;
        chargeAt =
          entity.status === 'created' &&
          (entity.type === 0 || entity.type === 2)
            ? entity.charge_at
            : null;

        if (entity.status === 'pending') {
          chargeAt = null;
        }

        let nextDueInvoice = this.getUpcomingInvoiceDetails(
          chargeAt,
          plan.item ? plan.item.amount * entity.quantity : 0,
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
        // inv_upcoming exists only for these subscriptions status only
        if (this.checkNextDueInvoiceValidity(entity.status, entity.type)) {
          // Charge at is not available in such type of subscriptions
          let chargeAt =
            entity.status === 'created' &&
            (entity.type === 0 || entity.type === 2)
              ? entity.charge_at
              : null;

          if (entity.status === 'pending') {
            chargeAt = null;
          }

          invoiceData = this.getUpcomingInvoiceDetails(
            chargeAt,
            plan.item.amount * entity.quantity,
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

      // If request is for /inv_upcoming then invoiceData will exist only if it's validInvoice.
      // And in this case InvoiceDetails won't show loader but error message
      invoiceSecView = (
        <InvoiceDetail
          mode={this.props.mode}
          curInvoiceIndex={this.state.curInvoiceIndex}
          nextChargeAt={entity.charge_at}
          invoice={invoiceData}
          subscription={entity}
          plan={plan}
          addons={addonsList}
          onClose={this.secClose}
          statusMsg={makeErrorStatus(invoiceErrors)}
          onManualAttempt={this.onManualAttempt}
          onAddOnDelete={this.deleteAddOn}
          showAddOnModal={this.showAddOnModal}
          isValidInvoice={isValidInvoice}
          isLoading={
            this.props.invoice_id === 'inv_upcoming' && invoiceData
              ? false
              : invoiceLoading
          }
          ref={comp => (this.invoiceView = comp)}
          subscriptionId={this.props.id}
        />
      );
    }

    return (
      <div class="multi-content">
        <SubscriptionDetails
          mode={this.props.mode}
          subscription={entity}
          plan={plan}
          customer={customer}
          invoices={invoicesList}
          isLoading={isLoading}
          statusMsg={makeErrorStatus(errors)}
          goToLink={this.goToLink}
          activeSecEntityId={activeSecEntityId}
          onCancelClick={this.cancelSubscription}
          onManualAttempt={this.onManualAttempt}
          onTestChargeAttempt={
            this.props.mode === 'test' &&
            (entity.status === 'created'
              ? this.onTestChargeAttemptWhileCreate
              : this.onTestChargeAttempt)
          }
        />

        {invoiceSecView}
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
  } else {
    return {};
  }
}
