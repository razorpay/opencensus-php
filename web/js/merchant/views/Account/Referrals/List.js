import React from 'react';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import Alert from 'common/ui/Forms/Alert';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import ShowWhen from 'merchant/components/ShowWhen';
import ListContainer from 'merchant/containers/ListContainer';
import * as ReferralActions from 'merchant/reducers/referrals';
import ReferralsList from 'merchant/views/Account/Referrals/components/ReferralsList';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import CreateLogin from './components/CreateLogin';
import CreateMerchant from './components/CreateMerchant';

@connect(
  (state) => {
    return {
      referrals: state.referrals,
      session: state.session,
    };
  },
  { ...ReferralActions, ...ModalActions, ...NotificationsActions },
)
class ReferralsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchReferrals(params);
  }

  showCreateLoginModal = (referral = null) => {
    this.props.openModal({
      component: (
        <CreateLogin
          referral={referral}
          onSave={(merchantId) => {
            return this.props.createLogin(merchantId);
          }}
        />
      ),
    });
  };

  switchMerchant = (merchantId) => {
    return this.props.switchMerchant(merchantId);
  };

  showCreateMerchantModal = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <CreateMerchant
          onSave={(params) => {
            return this.props.createMerchant(params);
          }}
        />
      ),
    });
  };

  render() {
    const { loading, referrals } = this.props.referrals;
    const user = this.props.session.user;
    const status = this.state.status;

    const isEditAllowed = user.isAllowedEdit('referrals');

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <ShowWhen additionalCondition={(user) => isEditAllowed && !user.isPartner()}>
            <div class="btn-toolbar">
              <button
                class="pull-right btn btn-primary"
                onClick={() => this.showCreateMerchantModal()}
              >
                <i class="i i-plus" />
                <span>New Merchant</span>
              </button>
            </div>
          </ShowWhen>
        </HeaderAction>

        <Alert type={status.type} message={status.message} />

        <ReferralsList
          referrals={referrals}
          isLoading={loading}
          user={user}
          showCreateLoginModal={isEditAllowed ? this.showCreateLoginModal : undefined}
          showCreateMerchantModal={isEditAllowed ? this.showCreateMerchantModal : undefined}
          switchMerchant={this.switchMerchant}
        />
      </div>
    );
  }
}

export default withRouter(ReferralsListContainer);
