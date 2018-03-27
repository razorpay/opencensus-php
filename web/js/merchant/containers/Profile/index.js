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
import BankAccountDetailsChange from './BankAccountDetailsChange';
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
    isBankAccountChangeAllowed: false,
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

    // fetch status whether the merchant can change their bank account details or not
    this.props
      .fetchBankAccountChangeStatus(this.props.user.id) //user.id is merchant_id not user_id
      .then(({ data }) => {
        this.setState({
          //if api response is true then the request is still in workflow
          isBankAccountChangeAllowed: !data,
        });
      })
      .catch(errors => {
        console.log('ERROR: Failed to fetch bank account change status');
      });
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

  openChangeBankDetailsModal = () => {
    const { bankAccount } = this.props.profile;

    this.props.openModal({
      size: 'large',
      component: (
        <BankAccountDetailsChange
          currentBankAccount={bankAccount}
          onSave={this.saveBankAccountChanges}
        />
      ),
    });
  };

  saveBankAccountChanges = data => {
    const { user } = this.props;
    let body = { ...data };
    let formdata = new FormData();

    //not needed
    delete body.account_number_confirmation;

    //required fields for api
    body.beneficiary_email = this.props.user.email;
    body.beneficiary_mobile = this.props.user.contact_mobile;

    for (let prop in body) {
      if (body.hasOwnProperty(prop)) {
        formdata.append(prop, body[prop]);
      }
    }

    return this.props
      .saveBankAccountChanges(user.id, formdata) //user.id is merchant_id not user_id
      .then(response => {
        this.props.closeModal();
        this.props.showNotification({
          type: 'success',
          message: 'Bank Account change request updated succesfully. ',
        });
        this.setState({ isBankAccountChangeAllowed: false });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
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
                <a class="pull-right" onClick={this.openChangePasswordModal}>
                  Change Password
                </a>
              </div>
            )}

            {user && user.current ? <MerchantDetails user={user} /> : null}
          </div>

          <ShowWhen myRole="owner finance">
            <GST />
          </ShowWhen>

          {bankAccount ? (
            <BankAccountDetails
              bankAccount={bankAccount}
              isBankAccountChangeAllowed={this.state.isBankAccountChangeAllowed}
              onChangeBankAccountDetails={this.openChangeBankDetailsModal}
            />
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
        </div>
      </div>
    );
  }
}
