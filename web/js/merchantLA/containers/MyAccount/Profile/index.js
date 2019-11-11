import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'rzp/ui/Forms/Alert';
import Spinner from 'rzp/ui/Spinner';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import ShowWhen from 'merchantLA/components/ShowWhen';

import User from 'merchant/models/User';
import MerchantDetails from 'merchantLA/components/MyAccount/Profile/MerchantDetails';
import BankAccountDetails from 'merchantLA/components/MyAccount/Profile/BankAccountDetails';
import { fetchUser } from 'merchantLA/reducers/session';
import PasswordForm from './PasswordForm';
import DisplayNameForm from 'merchant/components/Profile/DisplayNameForm';

import { updateDisplayName } from 'merchantLA/reducers/profile';
import { updateSession } from 'merchantLA/reducers/session';

@connect(
  state => {
    return {
      user: state.session.user,
    };
  },
  {
    ...ModalActions,
    showNotification,
    fetchUser,
    updateDisplayName,
    updateSession,
  }
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

  isLinkedAccountOwner() {
    return ['linked_account_owner'].indexOf(this.props.user.role) > -1;
  }

  openChangePasswordModal = () => {
    this.props.openModal({
      size: 'small',
      component: <PasswordForm />,
    });
  };

  updateDisplayName = props => {
    return this.props
      .updateDisplayName(props)
      .then(resp => {
        if (resp.success) {
          this.props.showNotification({
            type: 'success',
            message: 'Display name changed successfully.',
          });

          this.props.closeModal();

          const newUser = new User({
            ...this.props.user,
            display_name: resp.data.display_name,
          });

          this.props.updateSession({ user: newUser });
        }

        return resp;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  openChangeDisplayName = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <DisplayNameForm
          displayName={this.props.user.display_name}
          updateDisplayName={this.updateDisplayName}
        />
      ),
    });
  };

  render() {
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

            {user && user.current ? (
              <MerchantDetails
                user={user}
                changeDisplayName={
                  !!this.isLinkedAccountOwner() && this.openChangeDisplayName
                }
              />
            ) : null}
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
