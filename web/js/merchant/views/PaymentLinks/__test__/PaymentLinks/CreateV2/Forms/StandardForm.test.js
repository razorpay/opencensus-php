import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import {
  App,
  enablePayerNameProps,
} from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/StandardForm';

describe('StandardForm - Unit Test', () => {
  const renderApp = (props = {}, initalState) => {
    return render(<App {...props} />, { initalState, showModal: true });
  };

  test('should render "Standard Payment Link" form', () => {
    renderApp();
    expect(screen.getByText('Standard Payment Link')).toBeInTheDocument();
  });

  test('should call onSubmit & onCancel mock function', async () => {
    const onSubmitMock = jest.fn();
    const onCloseMock = jest.fn();
    const props = {
      isModalView: true,
      onSubmit: onSubmitMock,
      onClose: onCloseMock,
    };
    renderApp(props);
    expect(screen.getByText('Standard Payment Link')).toBeInTheDocument();
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

  test('should render isMobileResolution form elements', () => {
    const props = {
      isMobileResolution: true,
      showAnimationOnLoading: false,
    };
    renderApp(props);
    expect(screen.queryAllByText('Notify via Email')[0]).toBeInTheDocument();
  });

  test('should render "Payer Name"', () => {
    const props = {
      showPayerName: true,
      formData: {},
      remindersConfig: {},
    };
    const initialState = { ...enablePayerNameProps };
    renderApp(props, initialState);
    expect(screen.getByText('Payer Name')).toBeInTheDocument();
  });
});
