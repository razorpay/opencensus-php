import React, { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';
import DetailRow from '../../components/DetailRow';
import PasswordForm from './PasswordForm';
import UpgradeMerchantForm from './UpgradeMerchantForm';
import MerchantDetails from 'merchant/components/Profile/MerchantDetails';
import BankAccountDetails from 'merchant/components/Profile/BankAccountDetails';
import LoggedInUserDetails
  from 'merchant/components/Profile/LoggedInUserDetails';

import * as ModalActions from 'rzp/modules/modals';
import * as NotificationActions from 'rzp/modules/notifications';
import * as ProfileActions from 'merchant/modules/profile';

@connect(
  state => {
    return {
      user: state.session.user,
      profile: state.profile,
    };
  },
  { ...ProfileActions, ...NotificationActions, ...ModalActions }
)
export default class Profile extends Component {
  componentWillMount() {
    this.props.fetchBankInfoAndInvitations().catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });

    this.refreshUser(); // Analyze user object in props
  }

  refreshUser() {
    const { user } = this.props;
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
      hasMerchant,
    });

    // Show notification if user not assiciated with active merchant account
    if (!user.current) {
      this.props.showNotification({
        type: 'error',
        message: 'Your user account is not associated at present with any active merchant account.',
      });
    }
  }

  getPendingInvitations() {
    this.props.fetchPendingInvitations().catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
  }

  updateInvitation(type, invite) {
    let message;

    switch (type) {
      case 'accept':
        message = 'You have accepted the invite.';
        break;
      case 'reject':
        message = 'You have rejected the invite.';
        break;
      default:
        return;
    }

    this.props
      .updateInvitation(type, invite.id)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message,
        });
        if (type === 'reject') {
          this.getPendingInvitations();
        } else {
          location.reload(); //TODO: Check behavior, why it's needed
        }
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  }

  upgradeAccount = data => {
    return this.props
      .upgradeAccount(data)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Merchant Account Created.',
        });
        // $state.reload(); //TODO: angular, Check the purpose
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  askPwdConfirmation = () => {
    this.props.openModal({
      size: 'medium',
      component: (
        <PasswordForm
          changePassword={this.changePassword}
          closeModal={this.props.closeModal}
        />
      ),
    });
  };

  changePassword = values => {
    return this.props
      .updatePassword(values)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Password changed successfully.',
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    const { user, bankAccount, invitations } = this.props;
    let invitationList = null;

    if (!user) {
      return <div>Loading..</div>;
    }

    if (invitations) {
      let invites = [];
      invitations.forEach((invite, index) => {
        invites.push(
          <DetailRow
            key={index}
            label={`Invitation to join ${invite.merchant.name}`}
            value={() => (
              <div>
                <button
                  class="btn btn-xs btn-default"
                  onClick={() => this.updateInvitation('accept', invite)}
                >
                  Accept
                </button>
                <button
                  class="btn btn-xs btn-danger"
                  onClick={() => this.updateInvitation('reject', invite)}
                >
                  Reject
                </button>
              </div>
            )}
          />
        );
      });

      invitationList = (
        <div class="panel-detail-container">
          <div class="panel-heading">
            Pending Invitations
          </div>
          {invites}
        </div>
      );
    }

    return (
      <div class="react-root">
        <Header title="User Profile" showMode={false} />
        <div class="content-wrapper">
          <div class="row">
            <div class=" col-sm-6 col-sm-offset-3">
              <div class="panel panel-default">
                <div class="panel-heading">
                  Merchant Id: <strong>{1000000000}</strong>
                </div>

                <div class="panel-body">
                  {user && user.current
                    ? <MerchantDetails user={user} />
                    : null}
                  {bankAccount
                    ? <BankAccountDetails bankAccount={bankAccount} />
                    : null}
                  {this.state.merchantCount > 1 ||
                    this.state.loggedInUser.email != user.email
                    ? <LoggedInUserDetails
                        loggedInUser={this.state.loggedInUser}
                      />
                    : null}
                  {invitationList}

                  {!this.state.hasMerchant
                    ? <UpgradeMerchantForm
                        upgradeAccount={this.upgradeAccount}
                      />
                    : null}

                  <div class="text-center" style={{ margin: '25px 0' }}>
                    <button
                      class="btn btn-primary btn-rounded btn-change-pwd"
                      onClick={this.askPwdConfirmation}
                    >
                      Change Password
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
