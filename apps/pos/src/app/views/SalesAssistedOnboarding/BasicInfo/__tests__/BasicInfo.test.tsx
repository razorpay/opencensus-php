import React from 'react';
import BasicInfo from '../BasicInfo';
import { updateNameSuccess } from './mocks/handlers';
import { render, screen, userEvent, waitFor, server } from 'apps/pos/src/services/test/test-utils';

const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

describe('<BasicInfor/>', () => {
  const renderApp = () => {
    return render(<BasicInfo />);
  };
  test('should render input field and disabled button', () => {
    renderApp();
    expect(
      screen.getByText('Please check your name, the name should match your government ID card'),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Save & Continue' })).toBeDisabled();
  });

  test('should enable button after taking input from user redirect to /join after successfull submit', async () => {
    server.use(updateNameSuccess());
    renderApp();
    const inputElement = screen.getByPlaceholderText('Enter Your name');
    expect(inputElement).toBeInTheDocument();
    const saveAndContinueButton = screen.getByRole('button', { name: 'Save & Continue' });
    expect(saveAndContinueButton).toBeDisabled();
    await userEvent.type(inputElement, 'Test Name');
    await waitFor(() => {
      expect(saveAndContinueButton).not.toBeDisabled();
    });
    await userEvent.click(saveAndContinueButton);
    await waitFor(() => {
      expect(mockNavigate).toHaveBeenCalled();
    });
  });
});
