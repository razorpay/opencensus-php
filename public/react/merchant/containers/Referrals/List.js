import React, { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
import Header from 'rzp/ui/Header/Header';
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
    let { loading, referrals, highlightReferralId } = this.props.referrals;
    let user = this.props.session.user;
    let status = this.state.status;

    return (
      <div class="react-root">
        <Header title="Referrals" />
        <div class="content-wrapper">
          <div class="panel panel-default">
            <div class="panel-heading">
              Referrals
            </div>

            <Alert type={status.type} message={status.message} />

            <ReferralsList
              referrals={referrals}
              isLoading={loading}
              user={user}
              highlightRow={referral => referral.id === highlightReferralId}
              showCreateLoginModal={this.showCreateLoginModal}
              highlightReferralId={highlightReferralId}
              switchMerchant={this.switchMerchant}
            />

            {!loading && user.tags.indexOf('Aggregator') !== -1
              ? <div class="panel-footer">
                  <div class="row">
                    <div class="col-md-6 col-md-offset-3 col-sm-12 text-center">
                      <button
                        class="btn btn-primary"
                        onClick={this.showCreateMerchantModal}
                      >
                        Create New Merchant
                      </button>
                    </div>
                  </div>
                </div>
              : null}
          </div>
        </div>
      </div>
    );
  }
}
