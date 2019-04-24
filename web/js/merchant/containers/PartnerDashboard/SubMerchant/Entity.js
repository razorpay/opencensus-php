import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { fetchSubmerchant, resendInvite } from 'merchant/modules/submerchant';
import { switchMerchant } from 'merchant/modules/session';
import { openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import Entity from 'merchant/components/PartnerDashboard/Submerchant/Entity';

import InviteMerchant from './Invite';
import { trackListEvents } from '../ga';

@withRouter
@connect(
  state => ({
    ...state.submerchant,
  }),
  {
    fetchSubmerchant,
    resendInvite,
    switchMerchant,
    openModal,
    showNotification,
  }
)
export default class SubmerchantEntityContainer extends Component {
  componentWillMount() {
    this.props.fetchSubmerchant(this.props.id, this.props.appId);
  }

  componentDidMount() {
    if (!!this.props.closeUrl) {
      trackListEvents('Open Details');
    }
  }

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.id !== this.props.id ||
      nextProps.appId !== this.props.appId
    ) {
      this.props.fetchSubmerchant(nextProps.id, nextProps.appId);
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
        <Entity
          isLoading={loading}
          submerchant={submerchant}
          error={error}
          switchMerchant={switchMerchant}
          onInviteMerchant={this.handleInviteClick}
          onResendInvite={this.handleResendInvite}
        />
      </div>
    );
  }
}
