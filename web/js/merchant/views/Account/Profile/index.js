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
import User, { isOrgFeatureExist } from 'merchant/models/User';
import MerchantDetails from 'merchant/views/Account/Profile/components/MerchantDetails';
import Gst from 'merchant/views/Account/Profile/components/GST';
import BankAccountDetails from 'merchant/views/Account/Profile/components/BankAccountDetails';
import LoggedInUserDetails from 'merchant/views/Account/Profile/components/LoggedInUserDetails';
import Invitations from 'merchant/views/Account/Profile/components/Invitations';
import BankAccountDetailsChange, {
  BankVerificationErrors,
} from 'merchant/views/Account/Profile/components/BankAccountDetailsChange';
import { fetchUser, updateSession } from 'merchant/reducers/session';
import PasswordForm from 'merchant/views/Account/Profile/components/PasswordForm';
import MerchantConfigForm from 'merchant/views/Account/Profile/components/MerchantConfigForm';
import UpgradeMerchantForm from 'merchant/views/Account/Profile/components/UpgradeMerchantForm';
import SettlementDetails from 'merchant/views/Account/Profile/components/SettlementDetails';
import { fetchSettlementAmount } from 'merchant/reducers/home';
import rolesList from 'merchant/helpers/permissions/roles-list';
import SupportDetails from 'merchant/views/Account/Profile/components/SupportDetails';
import EmailSelfServeModal from 'merchant/views/Settings/EmailSelfServe/EmailInput';
import User2FASettings from './components/User2FASettings';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import UpdateBillingLabel from './components/UpdateBillingLabel';
import TwoFactorVerificationContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import IntoView from 'common/ui/IntoView';
import {
  SUPPORT_DETAILS,
  UPDATE_BANK_ACC,
  SETTELEMENT_CYCLE,
  NC_UPDATE_BANK_ACC,
  NC_UPDATE_GSTIN,
  VIEW_FIRC,
  RR_UPDATE_BANK_ACC,
  RR_UPDATE_GSTIN,
  UPDATE_GSTIN,
} from 'merchant/views/Account/Profile/deeplink-constants';
import { compose, bindActionCreators } from 'redux';
import NeedsClarificationModal from 'merchant/views/Account/Profile/components/WorkflowRequests/NeedsClarificationModal';
import {
  WORKFLOW_TYPES,
  getWorkflowTypeForRoute,
  getWorkflowNameForRoute,
} from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { isWorkflowInClarification } from 'merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const FIRCSection = lazy(() =>
  import(
    /* webpackChunkName: "FIRCSection" */ 'merchant/views/Account/Profile/components/FIRC/FIRCSection'
  ),
);
class Profile extends Component {
  state = {
    loggedInUser: {},
    //by default this feature is not available
    isBankAccountChangeAllowed: null,
    isWebsiteInWorkflow: null,
  };

  static contextType = TwoFactorVerificationContext;

  UNSAFE_componentWillMount() {
    this.props.fetchUser().then((reponse) => {
      const user = reponse.data;
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
        .catch(() => {
          console.log('ERROR: Failed to fetch bank account change status');
        });
    }

    this.props.fetchAddWebsiteWorkflowStatus().then(({ data }) => {
      this.setState({
        isWebsiteInWorkflow: data,
      });
    });
  }

  async openNeedsClarificationModal() {
    const { location, openModal, fetchWorkflowStatus } = this.props;

    if (!this.isAdminOrOwner() || !location || !location.pathname) return;

    const needsClarification = location.pathname.includes('clarification');
    const workflowRoute = location.pathname.split('/').pop();
    const workflowType = getWorkflowTypeForRoute(workflowRoute);
    const worfklowName = getWorkflowNameForRoute(workflowRoute);

    if (!needsClarification || !workflowRoute || !workflowType) return;
    const response = await fetchWorkflowStatus(workflowType);
    const workflow = response?.data;
    if (
      isWorkflowInClarification(workflow, ['open', 'approved']) &&
      workflow?.tags?.includes('awaiting-customer-response')
    ) {
      openModal({
        size: 'small',
        component: (
          <NeedsClarificationModal
            workflowType={workflowType}
            workflowName={worfklowName}
            refetch={false}
          />
        ),
      });
    }
  }

  componentDidMount() {
    this.openNeedsClarificationModal();

    const { profile } = this.props;
    //only call checkPassword api once
    if (profile.check_password.loading) {
      this.props.checkPassword();
    }
  }

  isAdminOrOwner() {
    return [rolesList.ADMIN, rolesList.OWNER].indexOf(this.props.user.role) > -1;
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    this.refreshUser(nextProps.user);
  }

  handleUpdateClick = () => {
    const { user } = this.props;
    analyticsTrack({
      objectName: 'Edit email',
      actionName: 'Clicked',
      screen: 'My account',
      properties: {
        location: 'profile',
        currentEmailId: user.email,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    return this.context.criticalFlow({
      modes: ['test', 'live'],
      onUserTwoFaVerified: () => {
        this.props.openModal({
          size: 'small',
          component: <EmailSelfServeModal />,
        });
        analyticsTrack({
          objectName: `Email 2fa result`,
          actionName: '2FA request',
          screen: 'My account',
          properties: {
            result: 'Success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      },
      onWrongOtpCallback: () => {
        analyticsTrack({
          objectName: `Email 2fa result`,
          actionName: '2FA request',
          screen: 'My account',
          properties: {
            result: 'Failure',
            reason: 'Wrong OTP submitted',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      },
    });
  };

  refreshUser(user) {
    if (!user.current) {
      return;
    }

    // Show notification if user not assiciated with active merchant account
    let hasMerchant = false;
    // Does the user have an associated merchant account
    // eslint-disable-next-line guard-for-in
    for (const i in user.user.merchants) {
      const merchant = user.user.merchants[i];
      if (merchant?.email?.toLowerCase() === user.user?.email?.toLowerCase()) {
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
    const message = 'You have accepted the invite.';

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
    const message = 'You have rejected the invite.';

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
          window.rzpAnalytics?.({
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
        window.rzpAnalytics?.({
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
    window.rzpAnalytics?.({
      eventCategory: 'Brand Name',
      eventAction: 'Edit brand name clicked',
      eventLabel: `${user.id}`,
    });

    this.props.openModal({
      size: 'med-large',
      component: (
        <UpdateBillingLabel
          attribute="billing_label"
          value={this.props.user.billing_label}
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

    window.rzpAnalytics?.({
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
    const body = { ...data };
    const formdata = new FormData();

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

    for (const prop in body) {
      if (body.hasOwnProperty(prop)) {
        formdata.append(prop, body[prop]);
      }
    }

    window.rzpAnalytics?.({
      eventCategory: 'Bank Account',
      eventAction: 'Bank account save clicked',
      eventLabel: `${user.id}`,
    });

    if (user.bankAccountAutoUpdateOrWorkflow()) {
      return this.props
        .saveBankAccountChangesAutomate(user.id, formdata) //user.id is merchant_id not user_id
        .then(({ data }) => {
          let message;
          if (data.new_bank_account && data.sync_flow === true) {
            message = 'Bank account details updated successfully.';
            this.props.fetchBankAccount();
          } else {
            message = 'Bank Account change request updated successfully.';
            this.props.fetchWorkflowStatus(WORKFLOW_TYPES.BANK_DETAIL_UPDATE);
            this.setState({ isBankAccountChangeAllowed: false });
          }
          this.props.showNotification({
            type: 'success',
            message,
          });
          this.props.closeModal();
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message:
              errors?.[0] in BankVerificationErrors ? BankVerificationErrors[errors[0]] : errors,
          });
        });
    }

    return this.props
      .saveBankAccountChanges(user.id, formdata) //user.id is merchant_id not user_id
      .then(() => {
        this.props.closeModal();
        this.props.fetchWorkflowStatus(WORKFLOW_TYPES.BANK_DETAIL_UPDATE);
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
    const { user, profile, settlement_amount } = this.props;
    const { bankAccount } = profile;
    const invitations = user.user.invitations;

    if (!user.isAuthenticated) {
      return (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      );
    }
    return (
      <div className="content-wrapper content-sm">
        <div className="profile-container">
          <Alert type="error" message={this.state.errors} showDismiss={false} />
          {user &&
          !user.user?.org_enforced_second_factor_auth &&
          (user.user?.signup_via_email || user.is2FAMobileSignupEnabled) ? (
            <User2FASettings />
          ) : null}
          <div className="panel panel-default">
            {user && user.current && (
              <div className="panel-heading">
                Merchant Id: <strong>{user.id}</strong>
                {user.user?.signup_via_email || profile.check_password.data.set_password ? (
                  <a className="pull-right" onClick={this.openChangePasswordModal}>
                    Change Password
                  </a>
                ) : null}
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
          <IntoView hashedWith={SUPPORT_DETAILS}>
            <SupportDetails />
          </IntoView>
          <ShowWhen
            additionalCondition={(usr) =>
              usr.isAllowedView('profile_gst') && !usr.isUnregisteredBusiness
            }
          >
            <IntoView hashedWith={[UPDATE_GSTIN, NC_UPDATE_GSTIN, RR_UPDATE_GSTIN]}>
              <Gst />
            </IntoView>
          </ShowWhen>
          <ShowWhen
            additionalCondition={() => bankAccount && !isOrgFeatureExist('hide_settlement_details')}
          >
            <IntoView hashedWith={[UPDATE_BANK_ACC, NC_UPDATE_BANK_ACC, RR_UPDATE_BANK_ACC]}>
              <BankAccountDetails
                bankAccount={bankAccount}
                isBankAccountChangeAllowed={this.state.isBankAccountChangeAllowed}
                settlement_amount={settlement_amount.data}
                onChangeBankAccountDetails={this.openChangeBankDetailsModal}
              />
            </IntoView>
          </ShowWhen>
          {this.state.loggedInUserRole === 'owner' ||
          this.state.merchantCount > 1 ||
          this.state.loggedInUser.email !== user.email ? (
            <LoggedInUserDetails
              isOrgRZP={user.isOrgRZP}
              loggedInUser={this.state.loggedInUser}
              loggedInUserRole={this.state.loggedInUserRole}
              handleUpdateClick={this.handleUpdateClick}
              isEmailSelfServeEnabled={user.isEmailSelfServeEnabled}
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
          {
            <IntoView hashedWith={SETTELEMENT_CYCLE}>
              <SettlementDetails />
            </IntoView>
          }
          {user.international && (
            <SuspenseWithLoader>
              <IntoView hashedWith={VIEW_FIRC}>
                <FIRCSection user={user} />
              </IntoView>
            </SuspenseWithLoader>
          )}
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    profile: state.profile,
    config: state.config.config,
    settlement_amount: state.home.settlement_amount,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ProfileActions,
      ...ModalActions,
      showNotification,
      fetchUser,
      updateSession,
      fetchSettlementAmount,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
    },
    dispatch,
  );
};

export default compose(
  connect(mapStateToProps, mapDispatchToProps),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('Profile')),
)(Profile);
