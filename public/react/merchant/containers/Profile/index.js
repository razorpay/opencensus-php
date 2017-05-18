import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
import Spinner from 'rzp/ui/Spinner';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import * as ProfileActions from 'merchant/modules/profile';

import MerchantDetails from 'merchant/components/Profile/MerchantDetails';
import BankAccountDetails from 'merchant/components/Profile/BankAccountDetails';
import LoggedInUserDetails
  from 'merchant/components/Profile/LoggedInUserDetails';
import Invitations from 'merchant/components/Profile/Invitations';
import { fetchUser } from 'merchant/modules/session';
import PasswordForm from './PasswordForm';
import UpgradeMerchantForm from './UpgradeMerchantForm';

@connect(
  state => {
    return {
      user: state.session.user,
      profile: state.profile,
    };
  },
  { ...ProfileActions, ...ModalActions, showNotification, fetchUser }
)
export default class Profile extends Component {
  state = {
    loggedInUser: {},
  };

  componentWillMount() {
    this.props.fetchUser().then(reponse => {
      let user = reponse.data;
      if (!user.current) {
        this.setState({
          errors: 'Your user account is not associated at present with any active merchant account.',
        });
      }
    });
    this.props.fetchBankAccount();
    this.props.fetchPendingInvitations();
    this.refreshUser(this.props.user);
  }

  componentWillReceiveProps(nextProps) {
    this.refreshUser(nextProps.user);
  }

  refreshUser(user) {
    if (!user.current) {
      return;
    }

    // Show notification if user not assiciated with active merchant account
    let hasMerchant = false;
    // Does the user have an associated merchant account
    for (let i in user.user.merchants) {
      var merchant = user.user.merchants[i];
      if (
        merchant.email &&
        merchant.email.toLowerCase() === user.user.email.toLowerCase()
      ) {
        hasMerchant = true;
      }
    }

    this.setState({
      merchantCount: Object.keys(user.merchants).length,
      loggedInUser: user.user,
      loggedInUserRole: user.merchants[user.id].pivot.role,
      hasMerchant,
    });
  }

  updateInvitation = (type, invite) => {
    let message = type === 'accept'
      ? 'You have accepted the invite.'
      : 'You have rejected the invite.';

    return this.props
      .updateInvitation(type, invite.id)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message,
        });
        if (type === 'reject') {
          this.props.fetchPendingInvitations();
        } else {
          setTimeout(() => {
            location.reload();
          }, 400);
        }
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  openChangePasswordModal = () => {
    this.props.openModal({
      size: 'small',
      component: <PasswordForm />,
    });
  };

  render() {
    const { user, profile } = this.props;
    const { bankAccount, invitations } = profile;

    if (!user) {
      return <div class="page-spinner-container"><Spinner /></div>;
    }

    return (
      <div class="content-wrapper content-sm">
        <div class="panel-detail-container">
          <Alert type="error" message={this.state.errors} showDismiss={false} />
          <div class="panel panel-default">
            {user.current &&
              <div class="panel-heading">
                Merchant Id: <strong>{user.id}</strong>
              </div>}

            <div class="panel-body">
              {user && user.current ? <MerchantDetails user={user} /> : null}

              {bankAccount
                ? <BankAccountDetails bankAccount={bankAccount} />
                : null}

              {this.state.merchantCount > 1 ||
                this.state.loggedInUser.email !== user.email
                ? <LoggedInUserDetails
                    loggedInUser={this.state.loggedInUser}
                    loggedInUserRole={this.state.loggedInUserRole}
                  />
                : null}

              {invitations.length
                ? <Invitations
                    invitations={invitations}
                    onAcceptClick={this.updateInvitation}
                    onRejectClick={this.updateInvitation}
                  />
                : null}

              {!this.state.hasMerchant ? <UpgradeMerchantForm /> : null}

              <div class="text-center">
                <button
                  class="btn btn-primary"
                  onClick={this.openChangePasswordModal}
                >
                  Change Password
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
