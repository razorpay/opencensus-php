import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { fetchSubmerchant } from 'merchant/modules/submerchant';
import { switchMerchant } from 'merchant/modules/session';
import { openModal } from 'rzp/modules/modals';

import Entity from 'merchant/components/PartnerDashboard/Submerchant/Entity';
import InviteMerchant from './Invite';

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
    userPartnerType: state.session.user.partner_type,
    ...state.submerchant,
  }),
  { fetchSubmerchant, switchMerchant, openModal }
)
export default class SubmerchantEntityContainer extends Component {
  componentWillMount() {
    this.props.fetchSubmerchant(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.id !== this.props.id) {
      this.props.fetchSubmerchant(nextProps.id);
    }
  }

  handleInviteClick = () => {
    this.props.openModal({
      size: 'small',
      component: <InviteMerchant />,
    });
  };

  render() {
    const {
      item: submerchant,
      loading,
      error,
      userPartnerType,
      switchMerchant,
    } = this.props;
    return (
      <div>
        <Entity
          isLoading={loading}
          submerchant={submerchant}
          error={error}
          showFullDetails={fullDetailsAccessMap[userPartnerType]}
          switchMerchant={switchMerchant}
          onInviteMerchant={this.handleInviteClick}
        />
      </div>
    );
  }
}
