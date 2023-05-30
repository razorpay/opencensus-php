import SuccessModal from 'merchant/views/PaymentButton/SubscriptionButton/components/SuccessModal';
import React from 'react';
import { userEvent, render } from 'test-utils';

describe('SuccessModal', () => {
  const paymentButton = {
    id: '123',
    title: 'Test Button',
    settings: {
      payment_button_theme: 'dark',
    },
  };

  const App = ({ ...rest }) => {
    return <SuccessModal paymentButton={paymentButton} {...rest} />;
  };

  it('renders the success message', () => {
    const { getByText } = render(<App />);
    expect(getByText('Button created successfully')).toBeInTheDocument();
  });

  it('renders the button title in the description', () => {
    const { getByText } = render(<App isEditExistingId={true} />);
    expect(getByText('Button updated successfully')).toBeInTheDocument();
  });

  it('renders the button title in the description', () => {
    const { getByText } = render(<App isEditExistingId={false} />);
    expect(getByText('Button created successfully')).toBeInTheDocument();
  });

  it('navigates to the button settings page when the button is clicked', async () => {
    const historyMock = { push: jest.fn() };
    const updateHighlightButtonSettingsMock = jest.fn();
    const onClickButtonSettingsMock = jest.fn();
    const { getByText } = render(
      <App
        history={historyMock}
        updateHighlightButtonSettings={updateHighlightButtonSettingsMock}
        onClickButtonSettings={onClickButtonSettingsMock}
      />,
    );
    await userEvent.click(getByText('Button Settings'));
    expect(updateHighlightButtonSettingsMock).toHaveBeenCalled();
    expect(onClickButtonSettingsMock).toHaveBeenCalled();
  });
});
