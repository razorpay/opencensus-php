import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import * as ProfileActions from 'merchant/reducers/profile';
import ShowWhen from 'merchant/components/ShowWhen';
import { analyticsTrack } from 'common/utils/analytics';

import User from 'merchant/models/User';
import MerchantDetails from 'merchant/views/Account/Profile/components/MerchantDetails';
import GST from 'merchant/views/Account/Profile/components/GST';
import BankAccountDetails from 'merchant/views/Account/Profile/components/BankAccountDetails';
import LoggedInUserDetails from 'merchant/views/Account/Profile/components/LoggedInUserDetails';
import Invitations from 'merchant/views/Account/Profile/components/Invitations';
import BankAccountDetailsChange from 'merchant/views/Account/Profile/components/BankAccountDetailsChange';
import { fetchUser } from 'merchant/reducers/session';
import PasswordForm from 'merchant/views/Account/Profile/components/PasswordForm';
import MerchantConfigForm from 'merchant/views/Account/Profile/components/MerchantConfigForm';
import UpgradeMerchantForm from 'merchant/views/Account/Profile/components/UpgradeMerchantForm';
import SettlementDetails from 'merchant/views/Account/Profile/components/SettlementDetails';
import { updateMerchantConfig, updateBillingLabel } from 'merchant/reducers/profile';
import { updateSession } from 'merchant/reducers/session';
import { fetchSettlementAmount } from 'merchant/reducers/home';
import rolesList from 'merchant/helpers/permissions/roles-list';
import SupportDetails from 'merchant/views/Account/Profile/components/SupportDetails';

import User2FASettings from './components/User2FASettings';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import UpdateBillingLabel from './components/UpdateBillingLabel';
import TwoFactorVerificationContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect(
  (state) => {
    return {
      user: state.session.user,
      profile: state.profile,
      config: state.config.config,
      settlement_amount: state.home.settlement_amount,
    };
  },
  {
    ...ProfileActions,
    ...ModalActions,
    showNotification,
    fetchUser,
    updateMerchantConfig,
    updateBillingLabel,
    updateSession,
    fetchSettlementAmount,
  },
)
@RTracking(() => window.rzpQ.component('Profile'))
export default class Profile extends Component {
  state = {
    loggedInUser: {},
    //by default this feature is not available
    isBankAccountChangeAllowed: null,
    isWebsiteInWorkflow: null,
  };

  static contextType = TwoFactorVerificationContext;

  componentWillMount() {
    this.props.fetchUser().then((reponse) => {
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

    this.props.fetchSettlementAmount();

    // fetch status whether the merchant can change their bank account details or not
    // Only allowed for role types `owner` & `admin`
    if (this.isAdminOrOwner()) {
      this.props
        .fetchBankAccountChangeStatus(this.props.user.id) //user.id is merchant_id not user_id
        .then(({ data }) => {
          this.setState({
            //if api response is true then the request is still in workflow
            isBankAccountChangeAllowed: !data,
          });
        })
        .catch((errors) => {
          console.log('ERROR: Failed to fetch bank account change status');
        });
    }

    this.props
      .fetchAddWebsiteWorkflowStatus()
      .then(({ data }) => {
        this.setState({
          isWebsiteInWorkflow: data,
        });
      })
      .catch((err) => {});
  }

  isAdminOrOwner() {
    return [rolesList.ADMIN, rolesList.OWNER].indexOf(this.props.user.role) > -1;
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
      if (merchant.email && merchant.email.toLowerCase() === user.user.email.toLowerCase()) {
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

  acceptInvitation = (invite) => {
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
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  rejectInvitation = (invite) => {
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
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.my_account_actions', {
      action: 'Change_Password_Initiated',
    }),
  )
  openChangePasswordModal = () => {
    analyticsTrack({
      objectName: 'change password',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'profile',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
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
          analyticsTrack({
            objectName: 'display name update',
            actionName: 'status',
            screen: 'my account',
            properties: {
              status: 'success',
              newDisplayName: props.display_name,
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
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
        analyticsTrack({
          objectName: 'display name update',
          actionName: 'status',
          screen: 'my account',
          properties: {
            status: 'failure',
            newDisplayName: props.display_name,
            failureReason: err.errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  openChangeDisplayName = () => {
    this.openAttrSaveModal('display_name');
  };

  updateBillingLabel = (data) => {
    return this.props
      .updateBillingLabel(data)
      .then((resp) => {
        if (resp.success) {
          window.rzpAnalytics({
            eventCategory: 'Brand Name',
            eventAction: 'Save brand name success',
            eventLabel: `${this.props.user.id}`,
          });

          this.props.showNotification({
            type: 'success',
            message: 'Brand name updated successfully.',
          });

          this.props.closeModal();

          const newUser = new User({
            ...this.props.user,
            billing_label: resp.data.billing_label,
          });

          this.props.updateSession({ user: newUser });
        }

        return resp;
      })
      .catch((err) => {
        window.rzpAnalytics({
          eventCategory: 'Brand Name',
          eventAction: 'Save brand name failure',
          eventLabel: `${this.props.user.id}`,
        });
        this.props.showNotification({
          type: 'error',
          message: err.errors[0],
        });
      });
  };

  openChangeBillingLabel = () => {
    const { user } = this.props;
    window.rzpAnalytics({
      eventCategory: 'Brand Name',
      eventAction: 'Edit brand name clicked',
      eventLabel: `${user.id}`,
    });

    this.props.openModal({
      size: 'med-large',
      component: (
        <UpdateBillingLabel
          attribute="billing_label"
          value={this.props.user['billing_label']}
          updateMerchantConfig={this.updateBillingLabel}
        />
      ),
      className: 'modal-white-background',
    });
  };

  openAttrSaveModal = (attr) => {
    this.props.openModal({
      size: 'small',
      component: (
        <MerchantConfigForm
          attribute={attr}
          label={ATTR_DETAILS[attr].label}
          desc={ATTR_DETAILS[attr].desc}
          value={this.props.user[attr]}
          updateMerchantConfig={this.updateMerchantConfig}
        />
      ),
    });
  };

  openChangeBankDetailsModal = () => {
    const { bankAccount } = this.props.profile;
    const { user } = this.props;

    window.rzpAnalytics({
      eventCategory: 'Bank Account',
      eventAction: 'Bank account edit clicked',
      eventLabel: `${user.id}`,
    });

    return this.context.criticalFlow({
      modes: ['live'],
      onUserTwoFaVerified: () => {
        this.props.openModal({
          size: 'large',
          component: (
            <BankAccountDetailsChange
              currentBankAccount={bankAccount}
              onSave={this.saveBankAccountChanges}
            />
          ),
        });
      },
      onBankAccountUpdateReq: true,
    });
  };

  saveBankAccountChanges = (data) => {
    const { user } = this.props;
    let body = { ...data };
    let formdata = new FormData();

    //not needed
    delete body.account_number_confirmation;
    analyticsTrack({
      objectName: 'Bank account save',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    //required fields for api
    body.beneficiary_email = this.props.user.email;
    body.beneficiary_mobile = this.props.user.contact_mobile;

    for (let prop in body) {
      if (body.hasOwnProperty(prop)) {
        formdata.append(prop, body[prop]);
      }
    }

    window.rzpAnalytics({
      eventCategory: 'Bank Account',
      eventAction: 'Bank account save clicked',
      eventLabel: `${user.id}`,
    });

    if (user.bankAccountAutoUpdateOrWorkflow()) {
      return this.props
        .saveBankAccountChangesAutomate(user.id, formdata) //user.id is merchant_id not user_id
        .then((response) => {
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
    }

    return this.props
      .saveBankAccountChanges(user.id, formdata) //user.id is merchant_id not user_id
      .then((response) => {
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

  onWebsiteAdd = () => {
    this.setState({
      isWebsiteInWorkflow: true,
    });
  };

  render() {
    let { user, profile, settlement_amount } = this.props;
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

          <User2FASettings />

          <div class="panel panel-default">
            {user.current && (
              <div class="panel-heading">
                Merchant Id: <strong>{user.id}</strong>
                <a class="pull-right" onClick={this.openChangePasswordModal}>
                  Change Password
                </a>
              </div>
            )}

            {user && user.current ? (
              <MerchantDetails
                user={user}
                changeDisplayName={!!this.isAdminOrOwner() && this.openChangeDisplayName}
                changeBillingLabel={!!this.isAdminOrOwner() && this.openChangeBillingLabel}
                isWebsiteInWorkflow={this.state.isWebsiteInWorkflow}
                onWebsiteAdd={this.onWebsiteAdd}
              />
            ) : null}
          </div>

          <SupportDetails />

          <ShowWhen
            additionalCondition={(user) =>
              user.isAllowedView('profile_gst') && !user.isUnregisteredBusiness
            }
          >
            <GST />
          </ShowWhen>

          {bankAccount ? (
            <BankAccountDetails
              bankAccount={bankAccount}
              isBankAccountChangeAllowed={this.state.isBankAccountChangeAllowed}
              settlement_amount={settlement_amount.data}
              onChangeBankAccountDetails={this.openChangeBankDetailsModal}
            />
          ) : null}

          {this.state.merchantCount > 1 || this.state.loggedInUser.email !== user.email ? (
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

          {!user.isMerchantRestricted && !this.state.hasMerchant ? <UpgradeMerchantForm /> : null}
          {<SettlementDetails />}
        </div>
      </div>
    );
  }
}
