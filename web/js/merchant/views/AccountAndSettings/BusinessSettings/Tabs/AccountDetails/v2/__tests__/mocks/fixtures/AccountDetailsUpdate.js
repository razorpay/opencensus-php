import React from 'react';
import { render } from 'test-utils';
import {
  PersonalProfileFields,
  HANDLERS,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import AccountDetailsUpdate from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/AccountDetails/v2/AccountDetailsUpdate';

jest.mock(
  'merchant/views/AccountAndSettings/common/components/ModalForm',
  () =>
    ({ entity: { id }, onUpdateClick }) =>
      (
        <div>
          {id} modal form{' '}
          <button
            type="button"
            onClick={() => onUpdateClick({ textInput: 'userInput', checkbox: true })}
          >
            Update
          </button>
        </div>
      ),
);

export const mockEntity = {
  id: PersonalProfileFields.DISPLAY_NAME,
  handlerType: HANDLERS.UPDATE,
  updateMerchantConfig: jest.fn(),
  onEmailUpdate: jest.fn(),
  onContactUpdateSubmit: jest.fn(),
};

export const onModalDismiss = jest.fn();
export const onEnterEmailSubmit = jest.fn();
export const onContactUpdateSubmit = jest.fn();

export const renderApp = ({ entity = mockEntity } = {}) => {
  return render(
    <AccountDetailsUpdate
      entity={entity}
      onModalDismiss={onModalDismiss}
      onEnterEmailSubmit={onEnterEmailSubmit}
      onContactUpdateSubmit={onContactUpdateSubmit}
    />,
  );
};
