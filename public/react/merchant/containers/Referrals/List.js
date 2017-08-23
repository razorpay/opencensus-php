import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import HeaderAction from 'rzp/ui/HeaderAction';
import Alert from 'rzp/ui/Forms/Alert';
import ShowWhen from 'merchant/components/ShowWhen';
import ListContainer from 'merchant/containers/ListContainer';
import ReferralsList from 'merchant/components/Referrals/ReferralsList';
import * as ReferralActions from 'merchant/modules/referrals';
import * as ModalActions from 'rzp/modules/modals';
import * as NotificationsActions from 'rzp/modules/notifications';
import CreateLogin from './CreateLogin';
import CreateMerchant from './CreateMerchant';

@connect(
  state => {
    return {
      referrals: state.referrals,
      session: state.session,
    };
  },
  { ...ReferralActions, ...ModalActions, ...NotificationsActions }
)
export default class ReferralsListContainer extends ListContainer {
  fetchEntityList(params) {
    return this.props.fetchReferrals(params);
  }

  showCreateLoginModal = (referral = null) => {
    this.props.openModal({
      component: (
        <CreateLogin
          referral={referral}
          onSave={params => {
            return this.props.createLogin(params);
          }}
        />
      ),
    });
  };

  switchMerchant = merchantId => {
    return this.props.switchMerchant(merchantId);
  };

  showCreateMerchantModal = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <CreateMerchant
          onSave={params => {
            return this.props.createMerchant(params);
          }}
        />
      ),
    });
  };

  render() {
    let { loading, referrals } = this.props.referrals;
    let user = this.props.session.user;
    let status = this.state.status;

    return (
      <div class="content-wrapper">
        <HeaderAction>
          <ShowWhen notMyRole="support">
            <div class="btn-toolbar">
              <button
                class="pull-right btn btn-primary"
                onClick={() => this.showCreateMerchantModal()}
              >
                <i class="icon icon-plus" />
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
          showCreateLoginModal={this.showCreateLoginModal}
          showCreateMerchantModal={this.showCreateMerchantModal}
          switchMerchant={this.switchMerchant}
        />
      </div>
    );
  }
}
