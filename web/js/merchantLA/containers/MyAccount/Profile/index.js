import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
import Spinner from 'rzp/ui/Spinner';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import ShowWhen from 'merchantLA/components/ShowWhen';

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

    if (!user.isAuthenticated) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    const bankAccount = {
      ifsc: user.bank_branch_ifsc,
      account_number: user.bank_account_number,
      name: user.bank_account_name,
    };

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

          <ShowWhen
            //myRole="owner"
            myRole="linked_account_owner"
          >
            <BankAccountDetails bankAccount={bankAccount} />
          </ShowWhen>
        </div>
      </div>
    );
  }
}
