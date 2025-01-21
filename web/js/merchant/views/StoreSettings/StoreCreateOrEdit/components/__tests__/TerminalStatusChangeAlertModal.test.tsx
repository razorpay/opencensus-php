import React from 'react';

import { screen, render, fireEvent } from 'test-utils';

import TerminalStatusChangeAlertModal from '../TerminalStatusChangeAlertModal';

describe('TerminalStatusChangeAlertModal', () => {
  test("should render 'TerminalStatusChangeAlertModal' component as expected", () => {
    const closeModal = jest.fn();
    const onSubmit = jest.fn();

    render(
      <TerminalStatusChangeAlertModal
        modalProps={{
          isOpen: true,
          onDismiss: closeModal,
        }}
        onSubmit={onSubmit}
      />,
    );
    expect(screen.getByText('Alert')).toBeInTheDocument();
    expect(screen.getByText('Click OK to continue')).toBeInTheDocument();
    const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    fireEvent.click(cancelBtn);
    expect(closeModal).toHaveBeenCalledTimes(1);

    const submitBtn = screen.getByRole('button', { name: 'OK' });
    expect(submitBtn).toBeInTheDocument();
    fireEvent.click(submitBtn);
    expect(onSubmit).toHaveBeenCalledTimes(1);
  });
});
