import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import { fetchSubmerchantWithProduct, resendInvite } from 'merchant/reducers/submerchant';
import { switchMerchant } from 'merchant/reducers/session';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import Details from 'merchant/views/PartnerDashboard/SubMerchant/components/Details';

import InviteMerchant from './Invite';
import { trackListEvents } from '../ga';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    ...state.submerchant,
  }),
  {
    fetchSubmerchantWithProduct,
    resendInvite,
    switchMerchant,
    openModal,
    showNotification,
  },
)
@RTracking(() => window.rzpQ.component('SubmerchantDetailsContainer '))
export default class SubmerchantDetailsContainer extends Component {
  state = {};

  componentWillMount() {
    let product = PRODUCT_TYPE.PG;
    if (this.props.history.location.pathname.startsWith('/partners/submerchants/x')) {
      product = PRODUCT_TYPE.X;
    }
    this.props.fetchSubmerchantWithProduct(this.props.id, this.props.appId, product);
  }

  componentDidMount() {
    if (!!this.props.closeUrl) {
      trackListEvents('Open Details');
    }
  }

  componentWillReceiveProps(nextProps) {
    let product = PRODUCT_TYPE.PG;
    if (this.props.history.location.pathname.startsWith('/partners/submerchants/x')) {
      product = PRODUCT_TYPE.X;
    }
    this.setState({
      product,
    });
    if (nextProps.id !== this.props.id || nextProps.appId !== this.props.appId) {
      this.props.fetchSubmerchantWithProduct(nextProps.id, nextProps.appId, product);
    }
  }

  componentWillUnmount() {
    this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
      action: 'cancel',
    });
  }

  getCurrentProduct = () => {
    if (this.props.history.location.pathname.startsWith('/partners/submerchants/x')) {
      return 'X';
    }
    return 'Payments';
  };

  trackUserEvent = (eventName, properties = {}) => {
    const { user, tracking } = this.props;
    const productGroup = this.getCurrentProduct();
    tracking.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        productGroup,
        ...properties,
      }),
    );
  };

  handleInviteClick = () => {
    this.props.openModal({
      size: 'small',
      component: <InviteMerchant />,
    });
  };

  handleResendInvite = () => {
    return this.props
      .resendInvite(this.props.id)
      .then((response) => {
        if (response.success) {
          this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
            action: 'Resend Invite',
            message: 'success',
          });
          this.props.showNotification({
            type: 'success',
            message: 'Merchant invited to manage dashboard successfully',
          });
        }
      })
      .catch(({ errors }) => {
        this.trackUserEvent('partnerships.dashboard.affiliate_account.account_selected', {
          action: 'Resend Invite',
          message: 'error',
          error: errors && errors[0],
        });
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { item: submerchant, loading, error, switchMerchant: _switchMerchant } = this.props;
    return (
      <div>
        <Details
          trackUserEvent={this.trackUserEvent}
          isLoading={loading}
          submerchant={submerchant}
          error={error}
          switchMerchant={_switchMerchant}
          onInviteMerchant={this.handleInviteClick}
          onResendInvite={this.handleResendInvite}
          product={this.state.product}
        />
      </div>
    );
  }
}
