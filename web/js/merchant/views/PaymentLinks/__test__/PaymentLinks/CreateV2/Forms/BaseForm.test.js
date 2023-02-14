import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { App } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/BaseForm';

describe('BaseForm - Unit Test', () => {
  const renderApp = (props = {}) => {
    return render(<App {...props} />, { showModal: true });
  };

  test('should render "Create Payment Link" Base form', () => {
    renderApp();
    expect(screen.getByText('Create Payment Link')).toBeInTheDocument();
  });

  test('should render Cancel Button in Modal View in Base form', async () => {
    const onCloseMock = jest.fn();
    const props = {
      isModalView: true,
      onClose: onCloseMock,
    };
    renderApp(props);
    const cancelButton = screen.getByRole('button', {
      name: 'Cancel',
    });
    expect(cancelButton).toBeInTheDocument();
    await userEvent.click(cancelButton);
    expect(onCloseMock).toHaveBeenCalledTimes(1);
  });
});
