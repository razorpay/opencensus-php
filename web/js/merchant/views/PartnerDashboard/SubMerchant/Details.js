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
import { isMobileAndTablet } from 'common/utils/rzp-utils';

@withRouter
@connect(
  (state) => ({
    user: state.session.user,
    isSubMerchantKycResellerEnabled: state.session.user.isSubMerchantKycResellerEnabled,
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

  constructor(props) {
    super(props);

    // if this feature is enabled - allows partner to perform submerchant kyc without requesting them
    this.isSubMerchantKYCAccess = this.props.user.isFeatureEnabled('partner_sub_kyc_access');
  }

  getPannelData = () => {
    let product = PRODUCT_TYPE.PG;
    if (this.props.history.location.pathname.startsWith('/partners/submerchants/x')) {
      product = PRODUCT_TYPE.X;
    }
    this.props.fetchSubmerchantWithProduct(this.props.id, this.props.appId, product);
  };

  UNSAFE_componentWillMount() {
    this.getPannelData();
  }

  componentDidMount() {
    if (!!this.props.closeUrl) {
      trackListEvents('Open Details');
    }
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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
    const { user, tracking, item: submerchant } = this.props;
    const activation_status = submerchant.details?.activation_status;
    const kyc_access_state = submerchant.kyc_access?.state;
    const rejection_count = submerchant.kyc_access?.rejection_count;
    const is_mweb = isMobileAndTablet();
    const productGroup = this.getCurrentProduct();

    tracking.trackEvent(
      window.rzpQ.onbr().interaction(eventName, {
        partnerID: user.id,
        submerchant_id: submerchant?.id,
        activation_status,
        kyc_access_state,
        rejection_count,
        is_mweb,
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
    const {
      item: submerchant,
      user,
      loading,
      error,
      switchMerchant: _switchMerchant,
      isSubMerchantKycResellerEnabled,
    } = this.props;
    return (
      <div>
        <Details
          getPannelData={this.getPannelData}
          isReseller={user.isPartner('reseller')}
          isSubMerchantKycResellerEnabled={isSubMerchantKycResellerEnabled}
          trackUserEvent={this.trackUserEvent}
          isLoading={loading}
          submerchant={submerchant}
          error={error}
          switchMerchant={_switchMerchant}
          onInviteMerchant={this.handleInviteClick}
          onResendInvite={this.handleResendInvite}
          product={this.state.product}
          isSubMerchantKYCAccess={this.isSubMerchantKYCAccess}
        />
      </div>
    );
  }
}
