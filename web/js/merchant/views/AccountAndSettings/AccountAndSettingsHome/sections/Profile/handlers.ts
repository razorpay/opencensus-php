import { Modules } from 'common/constant/enums';
import UpdateContactMobile from 'common/ui/UpdateContactMobile';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import User from 'merchant/models/User';
import { ATTR_DETAILS } from 'merchant/views/Account/constants';
import MerchantConfigForm from 'merchant/views/Account/Profile/components/MerchantConfigForm';
import PasswordForm from 'merchant/views/Account/Profile/components/PasswordForm';
import {
  FormConfigInterface,
  FormPayloadConfigInterface,
  PersonalProfileFields,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import EmailSelfServeModal from 'merchant/views/Settings/EmailSelfServe/EmailInput';
import AddEmailModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail';

export const updateDisplayNameHandler = (componentScope) => (attributes) => {
  return componentScope
    .updateMerchantConfig(attributes)
    .then((resp) => {
      if (resp.success) {
        selfServeTrackSuccess({
          selfServeAction: 'Display Name Updated',
          page: 'Personal Profile',
          screen: Modules.AccountAndSettings,
        });
        analyticsTrackWithUserInfo({
          objectName: 'display name update',
          actionName: 'status',
          screen: Modules.AccountAndSettings,
          properties: {
            status: 'success',
            newDisplayName: attributes.display_name,
          },
        });
        componentScope.showNotification({
          type: 'success',
          message: 'Display name changed successfully.',
        });
        componentScope.closeModal();
        const newUser = new User({
          ...componentScope.user,
          display_name: resp.data.display_name,
        });
        componentScope.updateSession({ user: newUser });
      }
      return resp;
    })
    .catch((err) => {
      componentScope.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
};

export const updateContactMobileHandler = (componentScope) => (userData) => {
  componentScope.updateUser(userData);
  componentScope.closeModal();
};

export const FORM_MAP: Record<
  Exclude<PersonalProfileFields, PersonalProfileFields.NAME>,
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
      Component: MerchantConfigForm,
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
  [PersonalProfileFields.EMAIL]: ({ handlerType }) => {
    const obj = handlerType === 'UPDATE' ? {} : { screen: 'my account' };
    return {
      attributes: obj,
      Component: handlerType === 'UPDATE' ? EmailSelfServeModal : AddEmailModal,
    };
  },
  [PersonalProfileFields.PASSWORD]: () => {
    return {
      Component: PasswordForm,
    };
  },
};
