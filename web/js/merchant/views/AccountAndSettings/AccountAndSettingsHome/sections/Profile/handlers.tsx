import React from 'react';
import { minLength, isEmail } from 'common/utils/validators';
import User from 'merchant/models/User';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import {
  FormConfigInterface,
  FormPayloadConfigInterface,
  PersonalProfileFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { handleUpdateAnalytics } from './utils';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { Modules } from 'common/constant/enums';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { addEmail } from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/services';
import lazy from 'merchant/routes/LazyLoader';

const AccountDetailsUpdate = lazy(
  () =>
    import(
      /* webpackChunkName: 'AccountDetailsUpdate' */ 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v2/AccountDetailsUpdate'
    ),
);

const AddEmail = lazy(
  () =>
    import(
      /* webpackChunkName: 'AddEmail' */ 'merchant/views/AccountAndSettings/common/components/AddEmail'
    ),
);

const DifferentTeam = lazy(
  () =>
    import(
      /* webpackChunkName: 'DifferentTeam' */ 'merchant/views/Settings/EmailSelfServe/components/DifferentTeam/DifferentTeam'
    ),
);

const SameTeam = lazy(
  () =>
    import(
      /* webpackChunkName: 'SameTeam' */ 'merchant/views/Settings/EmailSelfServe/components/SameTeam/SameTeam'
    ),
);

const NewID = lazy(
  () =>
    import(
      /* webpackChunkName: 'NewID' */ 'merchant/views/Settings/EmailSelfServe/components/NewId/NewID'
    ),
);

const PasswordForm = lazy(
  () =>
    import(
      /* webpackChunkName: 'PasswordForm' */ 'merchant/views/Account/Profile/components/PasswordForm'
    ),
);

const UpdateContactMobile = lazy(
  () => import(/* webpackChunkName: 'UpdateContactMobile' */ 'common/ui/UpdateContactMobile'),
);

export const updateDisplayNameHandler =
  (componentScope) =>
  ({ setIsLoading, ...attributes }, onSuccessCallback) => {
    setIsLoading(true);
    return componentScope
      .updateMerchantConfig(attributes)
      .then((resp) => {
        if (resp.success) {
          handleUpdateAnalytics({
            id: 'display_name',
            properties: {
              newDisplayName: attributes.display_name,
            },
          });
          componentScope.showNotification({
            type: 'success',
            message: 'Display name changed successfully.',
          });
          if (componentScope.closeModal) {
            componentScope.closeModal();
          }
          const newUser = new User({
            ...componentScope.user,
            display_name: resp.data.display_name,
          });
          componentScope.updateSession({ user: newUser });
          if (onSuccessCallback) {
            onSuccessCallback();
          }
        }
        return resp;
      })
      .catch((err) => {
        componentScope.showNotification({
          type: 'error',
          message: err.errors,
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

export const updateContactMobileHandler = (componentScope) => (userData) => {
  componentScope.updateUser(userData);
  componentScope.closeModal();
};

export const updateUserNameHandler =
  (componentScope) =>
  ({ setIsLoading, ...payload }, onSuccessCallback) => {
    setIsLoading(true);
    return componentScope
      .updateUserName(payload)
      .then((response) => {
        const { success, data } = response;
        if (success && data?.name) {
          handleUpdateAnalytics({
            id: 'name',
            properties: {
              newDisplayName: payload.name,
            },
          });
          componentScope.showNotification({
            type: 'success',
            message: 'User name changed successfully.',
          });
          componentScope.closeModal();
          if (onSuccessCallback) {
            onSuccessCallback();
          }
        }
        return response;
      })
      .catch((err) => {
        componentScope.showNotification({
          type: 'error',
          message: err.errors,
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

export const onEmailAdd =
  (componentScope) =>
  ({ email, otpAuthToken, setIsLoading }, onSuccessCallback) => {
    const { showNotification, screen = 'my account' } = componentScope;
    setIsLoading(true);
    analyticsTrackWithUserInfo({
      objectName: 'add email submit',
      actionName: 'clicked',
      screen,
    });
    if (!email || !isEmail(email)) {
      showNotification({
        type: 'error',
        message: 'Invalid email',
      });
      return;
    }

    addEmail({ email, otpAuthToken })
      .then((res) => {
        if (res.success) {
          analyticsTrackWithUserInfo({
            objectName: 'add email',
            actionName: 'result',
            screen,
            properties: {
              result: 'Success',
            },
          });
          onSuccessCallback?.();
        }
      })
      .catch((err) => {
        analyticsTrackWithUserInfo({
          objectName: 'add email',
          actionName: 'result',
          screen,
          properties: {
            result: 'Failure',
            failureMessage: err.errors[0] ? `${err.errors[0]}` : null,
          },
        });
        showNotification({
          type: 'error',
          message: err.errors[0] || 'Some error occured. Please refresh',
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

export const onEmailUpdate =
  (componentScope) =>
  ({ email, setContactEmail, setIsLoading }, onSuccessCallback) => {
    const { user, getEmailStatus, closeModal, openModal, showNotification } = componentScope;
    setIsLoading(true);
    analyticsTrackWithUserInfo({
      objectName: 'new email id',
      actionName: 'filled',
      screen: Modules.AccountAndSettings,
      properties: {
        location: 'profile',
        newEmailId: email,
        oldEmailId: user.email,
        updateContactEmail: setContactEmail,
      },
    });
    return getEmailStatus(email, setContactEmail)
      .then((res) => {
        closeModal();
        if (res.data && !res.data.is_user_exist) {
          selfServeTrackSuccess({
            selfServeAction: 'Login Details Updated',
            page: user.isAccountAndSettingsRevampEnabled ? 'Personal Profile' : 'Profile',
            screen: user.isAccountAndSettingsRevampEnabled ? 'Account & Settings' : 'My Account',
          });
          analyticsTrackWithUserInfo({
            objectName: 'email update invitation',
            actionName: 'sent',
            screen: Modules.AccountAndSettings,
            properties: {
              location: 'profile',
              newEmailId: email,
              oldEmailId: user.email,
            },
          });
          openModal({
            size: 'small',
            component: <NewID newEmail={email} />,
          });
        } else if (res.data?.is_team_member) {
          openModal({
            size: 'small',
            component: <SameTeam newEmail={email} />,
          });
        } else {
          openModal({
            size: 'small',
            component: <DifferentTeam newEmail={email} setContactEmail={setContactEmail} />,
          });
        }
        onSuccessCallback?.();
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors[0] || 'some error occurred',
        });
      })
      .finally(() => {
        setIsLoading(false);
      });
  };

export const FORM_MAP: Record<
  PersonalProfileFields,
  (arg0: FormPayloadConfigInterface) => FormConfigInterface
> = {
  [PersonalProfileFields.DISPLAY_NAME]: ({ props, id }) => {
    const obj = {
      attribute: id,
      label: ATTR_DETAILS[id].label,
      desc: ATTR_DETAILS[id].desc,
      value: props.user[id],
      updateMerchantConfig: updateDisplayNameHandler(props),
    };
    return {
      attributes: obj,
      Component: AccountDetailsUpdate,
    };
  },
  [PersonalProfileFields.CONTACT_MOBILE]: ({ props }) => {
    const obj = {
      onComplete: updateContactMobileHandler(props),
      page: 'Personal Profile',
    };
    return {
      attributes: obj,
      Component: UpdateContactMobile,
    };
  },
  [PersonalProfileFields.EMAIL]: ({ props, handlerType }) => {
    const obj =
      handlerType === 'UPDATE'
        ? {
            onEmailUpdate: onEmailUpdate(props),
          }
        : { onEmailAdd: onEmailAdd(props), screen: 'my account' };
    return {
      attributes: obj,
      Component: handlerType === 'UPDATE' ? AccountDetailsUpdate : AddEmail,
    };
  },
  [PersonalProfileFields.PASSWORD]: () => {
    return {
      Component: PasswordForm,
    };
  },
  [PersonalProfileFields.NAME]: ({ id, props }) => {
    const {
      user: {
        user: { [id]: value },
      },
    } = props;
    const attr = {
      attribute: id,
      config_type: 'name',
      label: ATTR_DETAILS[id].label,
      desc: ATTR_DETAILS[id].desc,
      value,
      validateConfig: [minLength(4)],
      updateMerchantConfig: updateUserNameHandler(props),
    };
    return {
      attributes: attr,
      Component: AccountDetailsUpdate,
    };
  },
};
