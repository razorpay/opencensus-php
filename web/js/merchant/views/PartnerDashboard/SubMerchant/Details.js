import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

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
  state => ({
    ...state.submerchant,
  }),
  {
    fetchSubmerchantWithProduct,
    resendInvite,
    switchMerchant,
    openModal,
    showNotification,
  }
)
export default class SubmerchantDetailsContainer extends Component {
  state={}

  componentWillMount() {
    let product = PRODUCT_TYPE.PG;
    if(this.props.history.location.pathname.startsWith('/partners/submerchants/x')) {
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
    if(this.props.history.location.pathname.startsWith('/partners/submerchants/x')) {
      product = PRODUCT_TYPE.X;
    }
    this.setState({
      product
    })
    if (
      nextProps.id !== this.props.id ||
      nextProps.appId !== this.props.appId
    ) {
      this.props.fetchSubmerchantWithProduct(nextProps.id, nextProps.appId, product)
    }
  }

  handleInviteClick = () => {
    this.props.openModal({
      size: 'small',
      component: <InviteMerchant />,
    });
  };

  handleResendInvite = () => {
    return this.props
      .resendInvite(this.props.id)
      .then(response => {
        if (response.success) {
          this.props.showNotification({
            type: 'success',
            message: 'Merchant invited to manage dashboard successfully',
          });
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { item: submerchant, loading, error, switchMerchant } = this.props;
    return (
      <div>
        <Details
          isLoading={loading}
          submerchant={submerchant}
          error={error}
          switchMerchant={switchMerchant}
          onInviteMerchant={this.handleInviteClick}
          onResendInvite={this.handleResendInvite}
          product={this.state.product}
        />
      </div>
    );
  }
}
