import React from 'react';
import ModalForm from 'merchant/views/AccountAndSettings/common/components/ModalForm';
import {
  PersonalProfileFields,
  HANDLERS,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import { AccountDetailsUpdateProps } from './types';

const AccountDetailsUpdate = ({
  entity,
  onEnterEmailSubmit,
  onContactUpdateSubmit,
  onModalDismiss,
}: AccountDetailsUpdateProps): JSX.Element => {
  const { id, handlerType, updateMerchantConfig, onEmailUpdate } = entity;
  const [isLoading, setIsLoading] = React.useState(false);
  const hasCheckbox = id === PersonalProfileFields.EMAIL && handlerType === HANDLERS.UPDATE;

  const onUpdateClick = ({ textInput: userInput, checkbox: setContactEmail }) => {
    switch (id) {
      case PersonalProfileFields.DISPLAY_NAME: {
        return updateMerchantConfig?.({ display_name: userInput, setIsLoading });
      }
      case PersonalProfileFields.NAME:
        return updateMerchantConfig?.({ name: userInput, setIsLoading });
      case PersonalProfileFields.CONTACT_MOBILE:
        return onContactUpdateSubmit?.({ contactMobile: userInput, setIsLoading });
      case PersonalProfileFields.EMAIL:
        if (handlerType === HANDLERS.UPDATE) {
          return onEmailUpdate?.({ email: userInput, setContactEmail, setIsLoading });
        }
        return onEnterEmailSubmit?.({ userInput, setIsLoading });
      default:
        return null;
    }
  };

  return (
    <ModalForm
      showModal={true}
      onModalDismiss={onModalDismiss}
      onUpdateClick={onUpdateClick}
      entity={entity}
      isLoading={isLoading}
      hasCheckbox={hasCheckbox}
    />
  );
};

export default AccountDetailsUpdate;
