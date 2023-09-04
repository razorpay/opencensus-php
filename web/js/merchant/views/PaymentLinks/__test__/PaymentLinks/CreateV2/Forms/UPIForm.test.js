import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { App } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/UPIForm';

describe('UPIForm - Unit Test', () => {
  const renderApp = (props = {}, initialState) => {
    return render(<App {...props} />, { initialState, showModal: true });
  };

  test('should call onSubmit & onCancel mock function in Modal View', async () => {
    const onSubmitMock = jest.fn();
    const onCloseMock = jest.fn();
    const props = {
      isModalView: true,
      onSubmit: onSubmitMock,
      onClose: onCloseMock,
      showAnimationOnLoading: true,
    };
    renderApp(props);
    expect(screen.getByText('UPI Payment Link')).toBeInTheDocument();
    const createPLButton = screen.getByRole('button', {
      name: 'Create Payment Link',
    });
    const cancelButton = screen.getByRole('button', {
      name: 'Cancel',
    });
    expect(createPLButton).toBeInTheDocument();
    expect(cancelButton).toBeInTheDocument();
    await userEvent.click(createPLButton);
    await userEvent.click(cancelButton);
    expect(onSubmitMock).toHaveBeenCalledTimes(1);
    expect(onCloseMock).toHaveBeenCalledTimes(1);
  });

  test('should render StandAloneContainer in UPI form elements', () => {
    const props = {
      isModalView: false,
    };
    const { container } = renderApp(props);
    expect(container.querySelector('.StandAloneContainer')).toBeInTheDocument();
  });
});
