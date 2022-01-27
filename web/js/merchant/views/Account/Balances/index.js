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
  CLICK_ON_MANAGE_ALERTS,
} from './ga';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import lazy from 'merchant/routes/LazyLoader';
import DocsLink from 'merchant/components/DocsLink';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ReserveBalance from 'merchant/views/Account/Balances/ReserveBalance';
import HeaderAction from 'common/ui/HeaderAction';
import CurrentBalance from 'merchant/views/Account/Balances/CurrentBalance';

const ManageBalanceAlert = lazy(() =>
  import(
    /* webpackChunkName: 'ManageBalanceAlert' */ 'merchant/views/Account/Balances/ManageBalanceAlert'
  ),
);

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

  handleManageAlert = () => {
    this.props.openModal({
      size: 'large',
      component: (
        <SuspenseWithLoader>
          <ManageBalanceAlert />
        </SuspenseWithLoader>
      ),
    });

    analyticsTrack(CLICK_ON_MANAGE_ALERTS);
  };

  render() {
    const { user } = this.props;
    const { ticketGenerated } = this.state;

    return (
      <div class="content-wrapper content-sm" style={{ backgroundColor: '#f9fafb' }}>
        <HeaderAction responsive>
          <div className="btn-toolbar pull-right">
            <DocsLink
              url="https://razorpay.com/docs/payment-gateway/dashboard-guide/balances/"
              onClick={() => analyticsTrack(OPEN_DOCUMENTATION)}
            />
          </div>
        </HeaderAction>
        <div class="balances-note-row">
          <span>Note: Standard TDR charges applies on adding funds</span>
          {user.isAllowedEdit('credits') && (
            <span style={{ color: '#528ff0' }} onClick={this.handleManageAlert}>
              Manage Alerts <i className="i i-bell-outline" />
            </span>
          )}
        </div>

        <CurrentBalance handleContactUs={this.handleContactUs} handlAddFunds={this.handlAddFunds} />

        <ReserveBalance
          handleContactUs={this.handleContactUs}
          handlAddFunds={this.handlAddFunds}
          handleActivate={this.handleActivate}
          ticketGenerated={ticketGenerated}
        />
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    ...state.session,
    account_balance: state.home.current_balance,
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
