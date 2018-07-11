import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
import Spinner from 'rzp/ui/Spinner';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { fetchBankAccount } from 'merchantLA/modules/profile';

import MerchantDetails from 'merchantLA/components/MyAccount/Profile/MerchantDetails';
import BankAccountDetails from 'merchantLA/components/MyAccount/Profile/BankAccountDetails';
import { fetchUser } from 'merchantLA/modules/session';
import PasswordForm from './PasswordForm';

@connect(
  state => {
    return {
      user: state.session.user,
    };
  },
  { ...ModalActions, showNotification, fetchUser }
)
export default class Profile extends Component {
  state = {};

  componentDidMount() {
    this.props.fetchUser().then(reponse => {
      let user = reponse.data;
      if (!user.current) {
        this.setState({
          errors:
            'Your user account is not associated at present with any active merchant account.',
        });
      }
    });

    fetchBankAccount()
      .then(response => {
        if (response.data) {
          this.setState({
            bankAccount: response.data,
          });
        }
      })
      .catch(err => {});
  }

  openChangePasswordModal = () => {
    this.props.openModal({
      size: 'small',
      component: <PasswordForm />,
    });
  };

  render() {
    console.log('re-render...');
    let { user } = this.props;
    let { bankAccount } = this.state;

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
