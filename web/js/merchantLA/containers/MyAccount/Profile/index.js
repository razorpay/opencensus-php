import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
import Spinner from 'rzp/ui/Spinner';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import * as ProfileActions from 'merchant/modules/profile';
import ShowWhen from 'merchant/components/ShowWhen';

import MerchantDetails from 'merchantLA/components/MyAccount/Profile/MerchantDetails';
import BankAccountDetails from 'merchantLA/components/MyAccount/Profile/BankAccountDetails';
import { fetchUser } from 'merchant/modules/session';
import PasswordForm from './PasswordForm';

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
      hasMerchant,
    });
  }

  openChangePasswordModal = () => {
    this.props.openModal({
      size: 'small',
      component: <PasswordForm />,
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
                Linked Account Details:
                <a class="pull-right" onClick={this.openChangePasswordModal}>
                  <b>Change Password</b>
                </a>
              </div>
            )}

            {user && user.current ? <MerchantDetails user={user} /> : null}
          </div>

          {bankAccount ? (
            <BankAccountDetails bankAccount={bankAccount} />
          ) : null}
        </div>
      </div>
    );
  }
}
