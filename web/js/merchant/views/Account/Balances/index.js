import React, { Component } from 'react';
import { connect } from 'react-redux';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { fetchCurrentBalance } from 'merchant/reducers/home';
import {
  fetchReserveBalance,
  storeTicketDetails,
  getTicketStatus,
} from 'merchant/reducers/profile';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import { bindActionCreators } from 'redux';
import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import { loadCheckout } from 'merchant/utils/fetchKeysAndCheckout';
import { analyticsTrack } from 'common/utils/analytics';
import {
  CLICK_ADD_FUNDS_ON_CURRENT_BALANCE,
  CLICK_ADD_FUNDS_ON_RESERVE_BALANCE,
  CURRENT_BALANCE_SUCCESS,
  CURRENT_BALANCE_FAILURE,
  RESERVE_BALANCE_SUCCESS,
  RESERVE_BALANCE_FAILURE,
  OPEN_DOCUMENTATION,
} from './ga';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { getCustomURL } from 'merchant/components/DocsLink';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const AddFundsForm = lazy(() =>
  import(/* webpackChunkName: 'AddFundsForm' */ 'common/ui/AddFundsForm'),
);

const AddCredits = lazy(() =>
  import(
    /* webpackChunkName: 'AddCredits' */ 'merchant/views/Account/Credits/components/AddCredits'
  ),
);

class AddFundsContainer extends Component {
  constructor(props) {
    super(props);
    this.state = {
      ticketGenerated: false,
    };
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

  addFunds = (transaction, type) => {
    return new Promise((resolve, reject) => {
      if (transaction.razorpay_payment_id) {
        resolve(true);
      } else {
        reject('Payment failed');
      }
    })
      .then((_) => {
        if (type === 'reserve') {
          this.props.fetchReserveBalance();
        }
        if (type === 'current') {
          this.props.fetchCurrentBalance();
        }
        this.props.showNotification({
          type: 'success',
          message: `${type === 'reserve' ? 'Reserve balance' : 'Funds'} added successfully`,
        });

        if (type === 'current') {
          analyticsTrack(CURRENT_BALANCE_SUCCESS);
        }

        if (type === 'reserve') {
          analyticsTrack(RESERVE_BALANCE_SUCCESS);
        }
      })
      .catch((_) => {
        if (type === 'current') {
          analyticsTrack(CURRENT_BALANCE_FAILURE);
        }

        if (type === 'reserve') {
          analyticsTrack(RESERVE_BALANCE_FAILURE);
        }
      });
  };

  analyticsHandler = (amount) => {
    analyticsTrack({
      objectName: 'add funds',
      actionName: 'result',
      screen: 'my account',
      properties: {
        status: 'success',
        location: 'balances',
        amount,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  statusHandler = ({ errors }) => {
    this.props.showNotification({
      type: 'error',
      message: `${errors}`,
    });
  };

  handlAddFunds = (type) => {
    this.props.openModal({
      size: 'small',
      component: (
        <SuspenseWithLoader>
          {type === 'current' ? (
            <AddFundsForm
              type={type}
              addHandler={this.addFunds}
              statusHandler={this.statusHandler}
              currentBalance={this.props.account_balance.data?.balance || 0}
              analyticsHandler={this.analyticsHandler}
              user={this.props.user}
            />
          ) : (
            <AddCredits
              type={type}
              addHandler={this.addFunds}
              statusHandler={this.statusHandler}
              analyticsHandler={this.analyticsHandler}
              user={this.props.user}
            />
          )}
        </SuspenseWithLoader>
      ),
    });

    if (type === 'current') {
      analyticsTrack({
        ...CLICK_ADD_FUNDS_ON_CURRENT_BALANCE,
        properties: {
          ...CLICK_ADD_FUNDS_ON_CURRENT_BALANCE.properties,
          currentBalance: this.props.account_balance.data?.balance || 0,
        },
      });
    } else {
      analyticsTrack(CLICK_ADD_FUNDS_ON_RESERVE_BALANCE);
    }
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
      window.rzpTicketSystem.addEventListener('ticket-created', this.handleTicketCreation);
      CreateTicketEmitter.emit('create-ticket', 'tickets');

      if (document.getElementsByName('request-description')?.length > 0) {
        setTimeout(() => {
          document.getElementsByName('request-description')[0].value =
            'Please activate reserve balance and share VA details';
        }, 1000);
      }
    }
  };

  handleTicketCreation = (response) => {
    const payload = {
      ticketNo: response?.data?.ticket_id,
      description: 'Please activate reserve balance and share VA details',
    };
    this.props.storeTicketDetails(payload);
    this.setState({
      ticketGenerated: true,
    });
  };

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
      window.rzpTicketSystem.addEventListener('ticket-created', this.handleTicketCreation);
      CreateTicketEmitter.emit('create-ticket', 'tickets');
    }
  };

  render() {
    const current_balance = this.props.account_balance.data?.balance || 0;
    const items = this.props.reserve_balance.data?.items;
    const reserveBalance = this.getReserveBalanceAmount(items);
    const { user } = this.props;
    const { data: ticketStatusData, loading: ticketStatusLoading } = this.props.ticket_status;

    return (
      <div class="content-wrapper content-sm" style={{ backgroundColor: '#f9fafb' }}>
        <div class="balances-note-row">
          <span>Note: Standard TDR charges applies on adding funds</span>
          <span class="text-primary">
            <a
              href={getCustomURL(
                'https://razorpay.com/docs/payment-gateway/dashboard-guide/balances/',
              )}
              target="_blank"
              rel="noopener noreferrer"
              onClick={() => {
                analyticsTrack(OPEN_DOCUMENTATION);
              }}
            >
              Learn more about Balances
            </a>
          </span>
        </div>
        <div class="balances-container">
          <div class="bal-cont-header">
            <div class="balances-lhs-container">
              <div class="balance-type-container">
                <p>Current Balance</p>
              </div>
              <div class="balance-amount-container">
                {current_balance < 0 && <p class="negative-marker">-</p>}
                <Amount
                  value={Math.abs(current_balance)}
                  currency="INR"
                  className={current_balance < 0 ? 'negative-balance' : ''}
                />
              </div>
            </div>
            <div class="balances-add-funds">
              {!user.isOrgAxis && (
                <button class="btn btn-outline" onClick={() => this.handlAddFunds('current')}>
                  Add Funds
                </button>
              )}
            </div>
          </div>

          <div class="bal-cont-footer">
            <p>
              Add funds to your account to process refunds/transfers when the account balance goes
              low. Adding large funds to your account?
              {/**/}
              <a onClick={this.handleContactUs}> Contact Us</a>
            </p>
          </div>
        </div>

        {ticketStatusLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="balances-container">
            <div class="bal-cont-header">
              <div class="balances-lhs-container">
                <div class="balance-type-container">
                  <p>Reserve Balance</p>
                </div>
                <div class="balance-amount-container">
                  <Amount
                    value={Math.abs(this.getReserveBalanceAmount(items))}
                    // value={Math.abs(reserve_balance)}
                    currency="INR"
                  />
                </div>
              </div>
              {!user.isOrgAxis && !user.isSelfServeCreditsEnabled && (
                <div class="balances-add-funds">
                  {this.state.ticketGenerated || ticketStatusData.ticket_status === 'Processing' ? (
                    <button class="btn btn-primary">Processing...</button>
                  ) : ticketStatusData.ticket_status === 'Resolved' ||
                    ticketStatusData.ticket_status === 'Closed' ||
                    reserveBalance > 0 ? null : (
                    <button class="btn btn-outline" onClick={this.handleActivate}>
                      Activate
                    </button>
                  )}
                </div>
              )}
              {!user.isOrgAxis && user.isSelfServeCreditsEnabled && (
                <div class="balances-add-funds">
                  <button class="btn btn-outline" onClick={() => this.handlAddFunds('reserve')}>
                    Add Funds
                  </button>
                </div>
              )}
            </div>
            <div class="bal-cont-footer">
              <p>
                Add funds to your reserve balance to increase the negative balance limit. Thinking
                of withdrawing your reserve balance?{' '}
                <a onClick={this.handleContactUs}>Contact Us</a>
              </p>
            </div>
          </div>
        )}

        {this.state.ticketGenerated ||
        (ticketStatusData.ticket_status === 'Processing' && !user.isSelfServeCreditsEnabled) ? (
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

const mapStateToProps = (state) => {
  return {
    ...state.session,
    account_balance: state.home.current_balance,
    reserve_balance: state.profile.reserve_balance,
    ticket_status: state.profile.ticket_status,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...NotificationsActions,
      ...ModalActions,
      fetchCurrentBalance,
      fetchReserveBalance,
      storeTicketDetails,
      getTicketStatus,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(AddFundsContainer);
