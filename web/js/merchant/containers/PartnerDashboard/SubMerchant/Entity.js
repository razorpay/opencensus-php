import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { fetchSubmerchant } from 'merchant/modules/submerchant';
import { switchMerchant } from 'merchant/modules/session';

import Entity from 'merchant/components/PartnerDashboard/Submerchant/Entity';

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
  { fetchSubmerchant, switchMerchant }
)
export default class SubmerchantEntityContainer extends Component {
  componentWillMount() {
    this.props.fetchSubmerchant(this.props.id.replace('acc_', ''));
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.id !== this.props.id) {
      this.props.fetchSubmerchant(nextProps.id.replace('acc_', ''));
    }
  }

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
        />
      </div>
    );
  }
}
