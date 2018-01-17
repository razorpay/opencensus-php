import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
import Spinner from 'rzp/ui/Spinner';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import * as ProfileActions from 'merchant/modules/profile';
import ShowWhen from 'merchant/components/ShowWhen';

import MerchantDetails from 'merchant/components/Profile/MerchantDetails';
import GST from 'merchant/containers/Profile/GST';
import BankAccountDetails from 'merchant/components/Profile/BankAccountDetails';
import LoggedInUserDetails from 'merchant/components/Profile/LoggedInUserDetails';
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
          errors:
            'Your user account is not associated at present with any active merchant account.',
        });
      }
    });
    this.props.fetchBankAccount();
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
      loggedInUserRole: user.userRole,
      hasMerchant,
    });
  }

  acceptInvitation = invite => {
    let message = 'You have accepted the invite.';

    return this.props
      .acceptInvitation(invite.id)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message,
        });
        setTimeout(() => {
          location.reload();
        }, 400);
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  rejectInvitation = invite => {
    let message = 'You have rejected the invite.';

    return this.props
      .rejectInvitation(invite.id, this.props.user.user.id)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message,
        });
        this.props.fetchUser();
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
    let { user, profile } = this.props;
    let { bankAccount } = profile;
    let invitations = user.user.invitations;

    if (!user.isAuthenticated) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    return (
      <div class="content-wrapper content-sm">
        <div class="profile-container">
          <Alert type="error" message={this.state.errors} showDismiss={false} />
          <div class="panel panel-default">
            {user.current && (
              <div class="panel-heading">
                Merchant Id: <strong>{user.id}</strong>
              </div>
            )}

            {user && user.current ? <MerchantDetails user={user} /> : null}
          </div>

          <ShowWhen myRole="owner finance">
            <GST />
          </ShowWhen>

          {bankAccount ? (
            <BankAccountDetails bankAccount={bankAccount} />
          ) : null}

          {this.state.merchantCount > 1 ||
          this.state.loggedInUser.email !== user.email ? (
            <LoggedInUserDetails
              loggedInUser={this.state.loggedInUser}
              loggedInUserRole={this.state.loggedInUserRole}
            />
          ) : null}

          {invitations.length ? (
            <Invitations
              invitations={invitations}
              onAcceptClick={this.acceptInvitation}
              onRejectClick={this.rejectInvitation}
            />
          ) : null}

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
    );
  }
}
