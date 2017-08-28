import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import SubscriptionDetails from 'merchant/components/Subscriptions/Details';
import InvoiceDetail from 'merchant/components/Subscriptions/InvoiceDetail';
import PaymentDetail from 'merchant/components/Payments/PaymentDetails';
import {
  fetchSubscription as fetchItem,
  cancelSubscription,
} from 'merchant/modules/subscriptions';
import { fetchPlan } from 'merchant/modules/plans';
import { fetchCustomer } from 'merchant/modules/customers';
import { fetchInvoice } from 'merchant/modules/invoices/details';
import { fetchItem as fetchPayment } from 'merchant/modules/payments/details';
import { showNotification } from 'rzp/modules/notifications';
import { expandSlider, compactSlider } from 'rzp/modules/slider';

@withRouter
@connect(state => state.subscription, {
  expandSlider,
  compactSlider,
  fetchInvoice,
  fetchPayment,
  fetchItem,
  fetchPlan,
  fetchCustomer,
  cancelSubscription,
  showNotification,
})
export default class SubscriptionDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {};

  componentWillMount() {
    this.fetchAll(this.props);
  }

  componentWillReceiveProps(nextProps) {
    this.fetchAll(nextProps);
  }

  fetchAll(nextProps) {
    let { id, invoice_id, payment_id } = nextProps;

    id && this.fetchSubscriptionDetails(id);
    invoice_id && this.fetchInvoice(invoice_id);
    payment_id && this.fetchPayment(payment_id);
  }

  fetchInvoice(id) {
    let { invoice } = this.props;
    if (!invoice || invoice.id !== id) {
      this.props.expandSlider();
      this.setState({ secView: 'invoice' });
      this.props
        .fetchInvoice(id)
        .then(invoice => {
          this.setState({
            invoice,
          });
        })
        .catch(({ errors }) => {
          this.setState({
            errors,
          });
        });
    }
  }

  fetchPayment(id) {
    let { payment } = this.props;
    if (!payment || payment.id !== id) {
      this.props.expandSlider();
      this.setState({ secView: 'payment' });
      this.props
        .fetchPayment(id)
        .then(payment => {
          this.setState({
            payment,
          });
        })
        .catch(({ errors }) => {
          this.setState({
            errors,
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
          ]);
        })
        .then(() => {
          this.setState({ isLoading: false });
        })
        .catch(({ errors }) => {
          this.setState({ errors, isLoading: false });
        });
    }
  }

  cancelSubscription = () => {
    this.context.confirm({
      header: 'Cancel Subscription?',
      message:
        "The subscription will be terminated and the customer's card will not be charged.",
      affirmativeLabel: 'Yes',
      abortLabel: 'No',
      action: () =>
        this.props
          .cancelSubscription(this.props.entity.id)
          .then(response => {
            this.props.showNotification({
              type: 'success',
              message: 'Subscription cancelled successfully',
            });
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          }),
    });
  };

  render() {
    let { entity, plan, customer } = this.props;
    let { invoice, payment, secView } = this.state;
    let errors = this.state.errors;
    let isLoading = this.state.isLoading;
    let statusMsg = {};

    if (errors) {
      statusMsg = {
        type: 'error',
        message: errors,
      };
    }

    return (
      <div class="multi-content">
        <SubscriptionDetails
          subscription={entity}
          plan={plan}
          customer={customer}
          isLoading={isLoading}
          statusMsg={statusMsg}
          onCancelClick={this.cancelSubscription}
        />
        {secView === 'invoice' &&
          <InvoiceDetail
            invoice={invoice}
            onClose={this.secClose}
            isLoading={secView && !invoice}
          />}
        {secView === 'payment' &&
          <PaymentDetail
            payment={payment}
            onClose={this.secClose}
            isLoading={secView && !payment}
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
