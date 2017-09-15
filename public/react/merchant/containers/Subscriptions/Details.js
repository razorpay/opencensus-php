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
import { showNotification } from 'rzp/modules/notifications';
import { expandSlider, compactSlider } from 'rzp/modules/slider';

import { openModal } from 'rzp/modules/modals';
import CancellationModal from './CancellationModal';

@withRouter
@connect(
  state => {
    return {
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
  }
)
export default class SubscriptionDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {};

  componentWillMount() {
    this.props.id && this.fetchSubscriptionDetails(this.props.id);
    this.checkSecView(); // Reset view
    this.props.invoice_id && this.fetchInvoice(this.props.invoice_id);
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

  fetchSubscriptionDetails(id) {
    let { entity, fetchItem, fetchPlan, fetchCustomer } = this.props;

    if (!entity || id !== entity.id) {
      this.setState({ isLoading: true });

      fetchItem(id)
        .then(subscription => {
          return Promise.all([
            fetchPlan(subscription.plan_id),
            fetchCustomer(subscription.customer_id),
            fetchSubscriptionAddOns(subscription.id)
              .then(response => {
                console.log('ADDONS..', response);
                this.setState({
                  addons: response.data.items,
                });

                return response.data; // To success the chain of Promise.all
              })
              .catch(err => {
                // Throw some error // To fail the chain of Promise.all. Will be caught below
              }),
          ]).then(response => {
            this.props.fetchInvoices(id).then(data => {
              // Set curInvoiceIndex when /{subscription_id}/{invoice_id} is direct hit
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
            this.setState({ isLoading: false });
          });
        })
        .catch(({ errors }) => {
          this.setState({ errors, isLoading: false });
        });
    }
  }

  goToLink = (itemId, index) => {
    this.setState({ curInvoiceIndex: index });
    this.props.history.push(`/subscriptions/${this.props.entity.id}/${itemId}`);

    if (this.invoiceView && findDOMNode(this.invoiceView)) {
      findDOMNode(this.invoiceView).classList.toggle('toggle-slider');
    }
  };

  cancelSubscription = () => {
    this.props.openModal({
      component: <CancellationModal subscriptionId={this.props.id} />,
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

    //TODO: Add addons list as well depending upon type in line_items
    return {
      id: 'inv_upcoming',
      status: 'next_due',
      issued_at: chargeAt,
      currency: 'INR',
      amount: planAmount + totalAddOnsAmount,
    };
  }

  // Manual Attempt to invoice charge
  onManualAttempt = invoiceId => {
    return paymentManualAttempt(invoiceId)
      .then(response => {
        // Show success notification
        this.props.showNotification({
          type: 'success',
          message: 'Manual charge attempt is successful',
        });

        // Fetch the list of invoices again

        return response;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
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
    if (!invoices.loading && invoices.items && invoices.items.length) {
      if (['authenticated', 'active', 'halted'].indexOf(entity.status) > -1) {
        invoicesList = { ...invoices };
        invoicesList.items = [...invoices.items]; // To avoid multiple additions when render is called multiple times

        let nextDueInvoice = this.getUpcomingInvoiceDetails(
          entity.charge_at,
          plan.item ? plan.item.amount : 0,
          this.state.addons
        );

        invoicesList.items.unshift(nextDueInvoice);
      }
    }

    // Secondary view : Invoice details
    if (secView === 'invoice') {
      let invoiceData;
      let isValidInvoice = true;

      if (
        this.props.invoice_id === 'inv_upcoming' &&
        Object.keys(entity).length // Helps to simulate the loader for 'inv_upcoming' invoice
      ) {
        // inv_upcoming exists only for these subscriptions status only
        if (['authenticated', 'active', 'halted'].indexOf(entity.status) > -1) {
          invoiceData = this.getUpcomingInvoiceDetails(
            entity.charge_at,
            plan.item.amount,
            this.state.addons
          );
        } else {
          isValidInvoice = false; // '/inv_upcoming' is invalid url for such subscriptions
        }
      }

      // If request is for /inv_upcoming then invoiceData will exist only if it's validInvoice.
      // And in this case InvoiceDetails won't show loader but error message
      invoiceSecView = (
        <InvoiceDetail
          curInvoiceIndex={this.state.curInvoiceIndex}
          subscriptionStatus={entity.status}
          nextChargeAt={entity.charge_at}
          invoice={invoiceData}
          onClose={this.secClose}
          statusMsg={makeErrorStatus(invoiceErrors)}
          onManualAttempt={this.onManualAttempt}
          isValidInvoice={isValidInvoice}
          isLoading={
            this.props.invoice_id === 'inv_upcoming' && invoiceData
              ? false
              : invoiceLoading
          }
          ref={comp => (this.invoiceView = comp)}
        />
      );
    }

    return (
      <div class="multi-content">
        <SubscriptionDetails
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
        />

        {invoiceSecView}
      </div>
    );
  }

  secClose = () => {
    let { compactSlider, history, location } = this.props;
    compactSlider();
    history.push(location.pathname.replace(/\/[^\/]+\/?$/, ''));
  };
}

function makeErrorStatus(message) {
  if (message) {
    return {
      type: 'error',
      message: invoiceErrors,
    };
  } else {
    return {};
  }
}
