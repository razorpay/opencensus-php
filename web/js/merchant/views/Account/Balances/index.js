import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'common/ui/Forms/Alert';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import {
  fetchReserveBalance,
  storeTicketDetails,
  getTicketStatus,
} from 'merchant/reducers/profile';
import { rupeesToPaise, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import AddFundsForm from 'merchant/views/Account/Balances/AddFundsForm';
import Amount from 'common/ui/Amount';
import { merchantFetch } from 'merchant/utils/ajax';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { analyticsTrack } from 'common/utils/analytics';
@connect(
  (state) => ({
    ...state.session,
    account_balance: state.home.current_balance,
    reserve_balance: state.profile.reserve_balance,
    ticket_status: state.profile.ticket_status,
  }),
  {
    ...NotificationsActions,
    ...ModalActions,
    fetchCurrentBalance,
    fetchReserveBalance,
    storeTicketDetails,
    getTicketStatus,
  },
)
export default class AddFundsContainer extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      isSaving: false,
      status: {},
      hasKeys: false,
      ticketGenerated: false,
    };
    this.handleTicketCreation = this.handleTicketCreation.bind(this);
  }

  componentDidMount() {
    analyticsTrack({
      objectName: 'balances',
      actionName: 'viewed',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.fetchCurrentBalance();
    this.props.fetchReserveBalance();
    this.props.getTicketStatus();

    // Loads checkout.js //
    loadCheckout(window.api_host);
  }

  fetchOrderId = (data) => {
    return merchantFetch({
      url: `orders`,
      method: 'post',
      data,
    });
  };

  addFunds(transaction) {
    this.setState({
      isSaving: true,
    });
    return new Promise((resolve, reject) => {
      if (transaction.razorpay_payment_id) {
        resolve(true);
      } else {
        reject('Payment failed');
      }
    })
      .then((_) => {
        this.setState({
          isSaving: false,
        });
        this.props.fetchCurrentBalance();
        this.props.showNotification({
          type: 'success',
          message: 'Funds added successfully',
        });
      })
      .catch((error) => {
        this.setState({
          isSaving: false,
          status: {
            type: 'error',
            message: error,
          },
        });
      });
  }

  openCheckout = async (fieldProps) => {
    let user = this.props.user;
    let amountInPaise = rupeesToPaise(fieldProps.amountInINR);

    try {
      var {
        data: { id },
      } = await this.fetchOrderId({
        amount: amountInPaise,
        currency: 'INR',
        payment_capture: 1,
      });
    } catch (e) {
      this.setState({
        status: {
          type: 'error',
          message: `An error occured - ${e.message}`,
        },
      });
      return;
    }

    let options = {
      order_id: id,
      amount: amountInPaise,
      description: fieldProps.description,
      amountInINR: fieldProps.amountInINR,
      prefill: {
        name: user.name,
        email: user.email,
        contact: user.contact_mobile,
      },
      notes: {
        dashboard: true,
      },
      handler: function (transaction = {}) {
        analyticsTrack({
          objectName: 'add funds',
          actionName: 'result',
          screen: 'my account',
          properties: {
            status: 'success',
            location: 'balances',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.addFunds({
          amount: amountInPaise,
          razorpay_payment_id: transaction.razorpay_payment_id,
        });
      }.bind(this),
    };

    return new Promise((resolve, reject) => {
      try {
        const rzp = new window.Razorpay(options);
        rzp.open();
        resolve();
      } catch (e) {
        reject(`An error occured - ${e.message}`);
      }
    }).catch((error) => {
      this.setState({
        status: {
          type: 'error',
          message: error,
        },
      });
    });
  };

  handlAddFunds = () => {
    analyticsTrack({
      objectName: 'add funds',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'balances',
        currentBalance: this.props.account_balance.data.balance,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.props.openModal({
      size: 'small',
      component: <AddFundsForm openCheckout={this.openCheckout} />,
    });
  };

  getReserveBalanceAmount = (items) => {
    if (!items) return 0;

    if (items.length === 0 || this.props.reserve_balance.error === true) {
      return 0;
    } else {
      return items[0].balance;
    }
  };

  handleActivate = () => {
    if (window.rzpTicketSystem && window.rzpTicketSystem.addEventListener) {
      const rzpTicketSystem = window.rzpTicketSystem;
      window.rzpTicketSystem.addEventListener('ticket-created', this.handleTicketCreation);
      rzpTicketSystem.setPrefill('#request', ['merchant', 'account-configuration-changes']);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
      setTimeout(() => {
        document.getElementsByName('request-description')[0].value =
          'Please activate reserve balance and share VA details';
      }, 1000);
    }
  };

  handleTicketCreation(response) {
    const payload = {
      ticketNo: response.ticketNo,
      description: 'Please activate reserve balance and share VA details',
    };
    this.props.storeTicketDetails(payload);
    this.setState({
      ticketGenerated: true,
    });
  }

  handleContactUs = () => {
    analyticsTrack({
      objectName: 'contact us',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'balances',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      window.rzpTicketSystem.addEventListener('ticket-created', this.handleTicketCreation);
      rzpTicketSystem.setPrefill('#request', ['merchant', 'account-configuration-changes']);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
    }
  };

  render() {
    const current_balance = this.props.account_balance.data.balance || 0;
    const items = this.props.reserve_balance.data.items;
    const reserveBalance = this.getReserveBalanceAmount(items);
    const { user } = this.props;

    return (
      <div class="content-wrapper content-sm" style={{ backgroundColor: '#f9fafb' }}>
        {Object.keys(this.state.status).length > 0 && (
          <Alert type={this.state.status.type} message={this.state.status.message} />
        )}
        <div class="balances-container">
          <div class="bal-cont-header">
            <div class="balances-lhs-container">
              <div class="balance-type-container">
                <p>Current Balance</p>
              </div>
              <div class="balance-amount-container">
                {current_balance < 0 && <p style={{ paddingRight: '5px', color: '#C42A2A' }}>-</p>}
                <Amount
                  value={Math.abs(current_balance)}
                  currency={'INR'}
                  className={current_balance < 0 ? 'negative-balance' : ''}
                />
              </div>
            </div>
            <div class="balances-add-funds">
              {!user.isOrgAxis && (
                <button class="btn btn-outline" onClick={this.handlAddFunds}>
                  Add Funds
                </button>
              )}
            </div>
          </div>

          <div class="bal-cont-footer">
            <p>
              Add funds to your account to process refunds/transfers when the account balance goes
              low. Adding large funds to your account?
              <a onClick={this.handleContactUs}> Contact Us</a>
            </p>
          </div>
        </div>

        <p style={{ paddingTop: '25px', paddingLeft: '2px' }}>
          Note: Standard TDR charges applies on adding funds.
        </p>

        <div
          style={{
            marginTop: '25px',
          }}
          class="balances-container"
        >
          <div class="bal-cont-header">
            <div class="balances-lhs-container">
              <div class="balance-type-container">
                <p>Reserve Balance</p>
              </div>
              <div class="balance-amount-container">
                <Amount value={Math.abs(this.getReserveBalanceAmount(items))} currency={'INR'} />
              </div>
            </div>
            {!user.isOrgAxis && (
              <div class="balances-add-funds">
                {this.state.ticketGenerated ||
                this.props.ticket_status.data.ticket_status === 'Processing' ? (
                  <button class="btn btn-primary">Processing...</button>
                ) : this.props.ticket_status.data.ticket_status === 'Resolved' ||
                  this.props.ticket_status.data.ticket_status === 'Closed' ||
                  reserveBalance > 0 ? null : (
                  <button class="btn btn-outline" onClick={this.handleActivate}>
                    Activate
                  </button>
                )}
              </div>
            )}
          </div>

          <div class="bal-cont-footer">
            <p>
              Add funds to your reserved balance to increase the negative balance limit(
              <a
                href="https://razorpay.com/docs/payment-gateway/balances/dashboard/"
                target="_blank"
              >
                Learn More
              </a>
              ). Withdraw reserved balance? <a onClick={this.handleContactUs}>Contact Us</a>
            </p>
          </div>
        </div>

        {this.state.ticketGenerated ||
        this.props.ticket_status.data.ticket_status === 'Processing' ? (
          <div class="processing-note">
            <p>
              Your request is being processed. Please check your registered email for an update.
            </p>
          </div>
        ) : null}
      </div>
    );
  }
}
