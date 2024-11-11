import React, { Component, Suspense } from 'react';
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
  ANALYTICS_OBJ,
} from './ga';
import lazy from 'merchant/routes/LazyLoader';
import DocsLink from 'merchant/components/DocsLink';
import ReserveBalance from 'merchant/views/Account/Balances/ReserveBalance';
import { CompanyBalance } from 'merchant/views/Account/Balances/CompanyBalance';
import CurrentBalance from 'merchant/views/Account/Balances/CurrentBalance';
import Loader from 'common/ui/Loader';
import { TicketSystemEmitter } from 'merchant/care/init';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { Flex } from '../Credits/components/style';
import { BellIcon, Box, Heading, Link, Text } from '@razorpay/blade/components';
import { isBillMeActivatedMerchant } from 'common/splitz/utils';

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

  analyticsHandler = (amount, type) => {
    selfServeTrackSuccess(ANALYTICS_OBJ[type]);
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
        <Suspense fallback={<Loader />}>
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
        </Suspense>
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
      selfServeTrackInitiate(ANALYTICS_OBJ[type]);
    } else {
      analyticsTrack(CLICK_ADD_FUNDS_ON_RESERVE_BALANCE);
      selfServeTrackInitiate(ANALYTICS_OBJ[type]);
    }
  };

  handleTicketCreation = (data = {}) => {
    const payload = {
      ticketNo: data?.ticket_id,
      description: 'Please activate reserve balance and share VA details',
    };
    this.props.storeTicketDetails(payload);
    this.setState({
      ticketGenerated: true,
    });
  };

  handleActivate = () => {
    TicketSystemEmitter.once('ticket-created', this.handleTicketCreation);
    if (window.rzpTicketSystem && window.rzpTicketSystem.openModal) {
      window.rzpTicketSystem.openModal(`#ticket`);

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
    TicketSystemEmitter.once('ticket-created', this.handleTicketCreation);
    if (window.rzpTicketSystem && window.rzpTicketSystem.openModal) {
      window.rzpTicketSystem.openModal(`#ticket`);
    }
  };

  handleManageAlert = () => {
    this.props.openModal({
      size: 'large',
      component: (
        <Suspense fallback={<Loader />}>
          <ManageBalanceAlert />
        </Suspense>
      ),
    });
    selfServeTrackInitiate({
      selfServeAction: 'Funds Alert Created',
      page: 'Addfunds',
      screen: 'My Account',
    });
    analyticsTrack(CLICK_ON_MANAGE_ALERTS);
  };

  render() {
    const { user } = this.props;
    const { ticketGenerated } = this.state;

    return (
      <div class="content-wrapper content-sm" style={{ backgroundColor: '#f9fafb' }}>
        <Box display="flex" flexDirection="column" gap="spacing.8">
          <Flex isResponsive justifyBetween alignItems="center">
            <Heading size="small" weight="semibold">
              Your Funds
            </Heading>
            <Box display="flex" gap="spacing.7" alignItems="center">
              {user.isAllowedEdit('credits') ? (
                <Link
                  variant="button"
                  icon={BellIcon}
                  iconPosition="right"
                  onClick={this.handleManageAlert}
                >
                  Manage Alerts
                </Link>
              ) : null}
              <DocsLink
                shouldUseBladeLink={true}
                url="https://razorpay.com/docs/payment-gateway/dashboard-guide/balances/"
                onClick={() => analyticsTrack(OPEN_DOCUMENTATION)}
              />
            </Box>
          </Flex>
          <Box gap="spacing.7" display="flex" flexDirection="column">
            <Box gap="spacing.5" display="flex" flexDirection="column">
              <CurrentBalance
                handleContactUs={this.handleContactUs}
                handlAddFunds={this.handlAddFunds}
              />
              <ReserveBalance
                handleContactUs={this.handleContactUs}
                handlAddFunds={this.handlAddFunds}
                handleActivate={this.handleActivate}
                ticketGenerated={ticketGenerated}
              />
            </Box>
            <Text size="small" weight="regular" color="surface.text.gray.normal">
              Note: Standard TDR charges applies on adding funds
            </Text>
            {isBillMeActivatedMerchant() ? <CompanyBalance /> : null}
          </Box>
        </Box>
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
