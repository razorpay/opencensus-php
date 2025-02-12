import { Component } from 'react';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { fetchProducts } from 'merchant/reducers/capital';
import { switchMerchant } from 'merchant/reducers/session';
import { fetchSubmerchantWithProduct, resendInvite } from 'merchant/reducers/submerchant';
import Details from 'merchant/views/PartnerDashboard/SubMerchant/components/Details';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { trackListEvents } from 'merchant/views/PartnerDashboard/ga';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import InviteMerchant from './Invite';

class SubmerchantDetailsContainer extends Component {
  state = {};

  constructor(props) {
    super(props);

    // if this feature is enabled - allows partner to perform submerchant kyc without requesting them
    this.isSubMerchantKYCAccess = this.props.user.isFeatureEnabled('partner_sub_kyc_access');
  }

  isCapitalProduct = () => {
    if (this.props.history.location.pathname.startsWith('/partners/submerchants/capital')) {
      return true;
    }
    return false;
  };
  getPannelData = () => {
    let product = PRODUCT_TYPE.PG;
    if (this.props.history.location.pathname.startsWith('/partners/submerchants/x')) {
      product = PRODUCT_TYPE.X;
    }

    if (this.isCapitalProduct()) {
      product = PRODUCT_TYPE.CAPITAL;
    }

    this.setState({
      product,
    });
    this.props.fetchSubmerchantWithProduct(this.props.id, this.props.appId, product);
  };

  componentDidMount() {
    const { fetchProducts } = this.props;
    if (this.isCapitalProduct()) {
      fetchProducts();
    }

    this.getPannelData();
    if (!!this.props.closeUrl) {
      trackListEvents('Open Details');
    }
  }

  componentDidUpdate(prevProps) {
    if (prevProps.id !== this.props.id || prevProps.appId !== this.props.appId) {
      this.getPannelData();
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
    } else if (this.props.history.location.pathname.startsWith('/partners/submerchants/capital')) {
      return 'Capital';
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
      .resendInvite(this.props.id, this.state.product)
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
          message: errors[0],
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
      capitalProducts,
    } = this.props;
    return (
      <Details
        getPannelData={this.getPannelData}
        isReseller={user.isPartner('reseller')}
        isSubMerchantKycEnabled={user.isSubMerchantKycEnabled}
        isSubMerchantKYCAccess={this.isSubMerchantKYCAccess}
        trackUserEvent={this.trackUserEvent}
        isLoading={loading}
        submerchant={submerchant}
        error={error}
        switchMerchant={_switchMerchant}
        onInviteMerchant={this.handleInviteClick}
        onResendInvite={this.handleResendInvite}
        product={this.state.product}
        capitalProducts={capitalProducts}
      />
    );
  }
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      capitalProducts: state.loanApplicationDetails.products,
      ...state.submerchant,
    }),
    {
      fetchSubmerchantWithProduct,
      resendInvite,
      switchMerchant,
      openModal,
      showNotification,
      fetchProducts,
    },
  ),
  rTracking(() => window.rzpQ.component('SubmerchantDetailsContainer ')),
  withRouter,
)(SubmerchantDetailsContainer);
