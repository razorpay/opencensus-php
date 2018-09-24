import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { fetchSubmerchant } from 'merchant/modules/submerchant';
import { switchMerchant } from 'merchant/modules/session';
import { openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import Entity from 'merchant/components/PartnerDashboard/Submerchant/Entity';

import InviteMerchant from './Invite';
import { trackListEvents } from '../ga';

const fullDetailsAccessMap = {
  fully_managed: true,
  aggregator: true,
  bank: false,
  reseller: false,
  pure_platform: false,
};

@withRouter
@connect(
  state => ({
    ...state.submerchant,
  }),
  { fetchSubmerchant, switchMerchant, openModal, showNotification }
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
        />
      </div>
    );
  }
}
