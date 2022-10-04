import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, fireEvent } from 'test-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import BankAccountDetailsChange from '../BankAccountDetailsChange';

const buttonText = 'Submit and verify';

describe('Bank account update status component', () => {
  const closeModal = jest.spyOn(ModalActions, 'closeModal');

  beforeEach(() => closeModal.mockClear());
  const App = () => {
    return <BankAccountDetailsChange />;
  };
  test('should render init form by default', () => {
    const { getByRole } = render(<App />);
    const ctaBtn = getByRole('button', {
      name: buttonText,
    });
    expect(ctaBtn).toBeInTheDocument();
  });
  test('should have close button and it should invoke closeModal', () => {
    const { getByTestId } = render(<App initialState={{}} />);
    const closeBtn = getByTestId('modal-header-close-btn');
    expect(closeBtn).toBeInTheDocument();
    fireEvent.click(closeBtn);
    expect(closeModal).toHaveBeenCalled();
  });
});
