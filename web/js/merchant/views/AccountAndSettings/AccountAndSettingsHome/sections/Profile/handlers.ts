import UpdateContactMobile from 'common/ui/UpdateContactMobile';
import { minLength } from 'common/utils/validators';
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
import { handleUpdateAnalytics } from './utils';

export const updateDisplayNameHandler = (componentScope) => (attributes, onSuccessCallback) => {
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
    });
};

export const updateContactMobileHandler = (componentScope) => (userData) => {
  componentScope.updateUser(userData);
  componentScope.closeModal();
};

export const updateUserNameHandler = (componentScope) => (payload, onSuccessCallback) => {
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
      Component: MerchantConfigForm,
    };
  },
};
