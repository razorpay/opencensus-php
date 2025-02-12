import React, { Component } from 'react';
import { connect } from 'react-redux';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import ShowWhen from 'merchantLA/components/ShowWhen';

import User from 'merchant/models/User';
import MerchantDetails from 'merchantLA/components/MyAccount/Profile/MerchantDetails';
import BankAccountDetails from 'merchantLA/components/MyAccount/Profile/BankAccountDetails';
import { fetchUser } from 'merchantLA/reducers/session';
import PasswordForm from './PasswordForm';
import MerchantConfigForm from 'merchant/views/Account/Profile/components/MerchantConfigForm';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { updateMerchantConfig } from 'merchantLA/reducers/profile';
import { updateSession } from 'merchantLA/reducers/session';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import { compose } from 'redux';

class Profile extends Component {
  state = {};

  componentDidMount() {
    this.props.fetchUser().then((reponse) => {
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

  updateMerchantConfig = (props) => {
    return this.props
      .updateMerchantConfig(props)
      .then((resp) => {
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
      .catch((err) => {
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
        <MerchantConfigForm
          attribute="display_name"
          label={ATTR_DETAILS.display_name.label}
          desc={ATTR_DETAILS.display_name.desc}
          value={this.props.user.display_name}
          updateMerchantConfig={this.updateMerchantConfig}
        />
      ),
    });
  };

  render() {
    let { user } = this.props;

    if (!user.isAuthenticated) {
      return (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    const bankAccount = {
      ifsc: user.bank_branch_ifsc,
      account_number: user.bank_account_number,
      name: user.bank_account_name,
      bank_name: user.bank_name,
    };

    return (
      <div className="content-wrapper content-sm">
        <div className="profile-container">
          <Alert type="error" message={this.state.errors} showDismiss={false} />
          <div className="panel panel-default">
            {user.current && (
              <div className="panel-heading">
                Linked Account Details:
                <a className="pull-right" onClick={this.openChangePasswordModal}>
                  <b>Change Password</b>
                </a>
              </div>
            )}

            {user && user.current ? (
              <MerchantDetails
                user={user}
                changeDisplayName={!!this.isLinkedAccountOwner() && this.openChangeDisplayName}
              />
            ) : null}
          </div>

          <ShowWhen
            //myRole="owner"
            myRole="linked_account_owner"
          >
            <BankAccountDetails
              bankAccount={bankAccount}
              isCountryIndia={user.merchant.country_code === 'IN'}
            />
          </ShowWhen>
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        user: state.session.user,
      };
    },
    {
      ...ModalActions,
      showNotification,
      fetchUser,
      updateMerchantConfig,
      updateSession,
    },
  ),
)(Profile);
