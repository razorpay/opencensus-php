import React from 'react';
import { render } from 'test-utils';
import ModalForm from 'merchant/views/AccountAndSettings/common/components/ModalForm';

export const mockEntity = { id: 'password' };
export const mockUser = {
  user: {
    email: 'something@gmail.com',
  },
};

export const onUpdateClick = jest.fn();
export const onModalDismiss = jest.fn();

export const renderApp = ({ user = {}, entity = mockEntity, ...rest } = {}) => {
  return render(
    <ModalForm
      entity={entity}
      showModal={true}
      onModalDismiss={onModalDismiss}
      onUpdateClick={onUpdateClick}
      {...rest}
    />,
    {
      initialState: {
        session: {
          user: {
            ...mockUser,
            ...user,
          },
        },
      },
    },
  );
};
