import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import SubscriptionDetails from 'merchant/components/Subscriptions/Details';
import InvoiceDetail from 'merchant/components/Invoices/InvoiceDetail';
import {
  fetchSubscription as fetchItem,
  cancelSubscription,
} from 'merchant/modules/subscriptions';
import { fetchPlan } from 'merchant/modules/plans';
import { fetchCustomer } from 'merchant/modules/customers';
import { fetchInvoice } from 'merchant/modules/invoices/details';
import { showNotification } from 'rzp/modules/notifications';
import { expandSlider, compactSlider } from 'rzp/modules/slider';

@withRouter
@connect(state => state.subscription, {
  expandSlider,
  compactSlider,
  fetchInvoice,
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
    this.fetchSubscriptionDetails(this.props.id);
    if (this.props.invoice_id) {
      this.fetchInvoice(this.props.invoice_id);
    }
    if (this.props.payment_id) {
      this.fetchPayment(this.props.payment_id);
    }
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  fetchInvoice(id) {
    this.props.expandSlider();
    this.setState({ secView: true });
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
    let { invoice, secView } = this.state;
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
        <InvoiceDetail invoice={invoice} isLoading={secView && !invoice} />
        <button
          type="button"
          class="close close-secondary"
          onClick={this.secClose}
        >
          <i class="icon icon-close" />
        </button>
      </div>
    );
  }

  secClose = () => {
    let { compactSlider, history, location } = this.props;

    compactSlider();
    setTimeout(
      () => history.push(location.pathname.replace(/\/[^\/]+\/?$/, '')),
      300
    );
  };
}
