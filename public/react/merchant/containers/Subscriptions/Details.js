import React, { Component, PropTypes } from 'react';
import { findDOMNode } from 'react-dom';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import SubscriptionDetails from 'merchant/components/Subscriptions/Details';
import InvoiceDetail from 'merchant/components/Subscriptions/InvoiceDetail';
import {
  fetchSubscription as fetchItem,
  fetchInvoices,
} from 'merchant/modules/subscriptions';
import { fetchPlan } from 'merchant/modules/plans';
import { fetchCustomer } from 'merchant/modules/customers';
import { fetchInvoice } from 'merchant/modules/invoices/details';
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
    this.props.invoice_id && this.fetchInvoice(this.props.invoice_id);
    this.checkSecView();
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
        findDOMNode(this.invoiceView).classList.toggle('toggle-slider');
      }

      this.setState({
        invoice: {},
        invoiceLoading: false,
      });
    }
  }

  fetchInvoice(id) {
    let { invoice } = this.props;
    if (!invoice || invoice.id !== id) {
      this.props.expandSlider();
      this.setState({
        secView: 'invoice',
        invoiceErrors: null,
        invoiceLoading: true,
      });
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
          ]).then(() => {
            this.props.fetchInvoices(id).then(data => {
              // Set curInvoiceIndex when /{subscription_id}/{invoice_id} is direct hit
              if (data.data && !this.state.curInvoiceIndex) {
                const invoicesItems = data.data.items;

                invoicesItems.forEach((item, index) => {
                  if (item.id === this.props.invoice_id) {
                    this.setState({
                      curInvoiceIndex: invoicesItems.length - index,
                    });
                  }
                });
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

    return (
      <div>
        <SubscriptionDetails
          subscription={entity}
          plan={plan}
          customer={customer}
          invoices={invoices}
          isLoading={isLoading}
          statusMsg={makeErrorStatus(errors)}
          goToLink={this.goToLink}
          activeSecEntityId={activeSecEntityId}
          onCancelClick={this.cancelSubscription}
        />
        {false &&
          secView === 'invoice' &&
          <InvoiceDetail
            curInvoiceIndex={this.state.curInvoiceIndex}
            invoice={invoice}
            onClose={this.secClose}
            statusMsg={makeErrorStatus(invoiceErrors)}
            isLoading={secView && invoiceLoading}
            ref={comp => (this.invoiceView = comp)}
          />}
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
  return (
    message && {
      type: 'error',
      message: invoiceErrors,
    }
  );
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
