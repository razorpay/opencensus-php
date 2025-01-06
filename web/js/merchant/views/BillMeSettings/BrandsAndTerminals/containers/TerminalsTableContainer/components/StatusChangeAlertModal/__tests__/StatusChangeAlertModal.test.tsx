import React from 'react';

import StatusChangeAlertModal from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/components/StatusChangeAlertModal';
import { screen, render, fireEvent } from 'test-utils';

describe('StatusChangeAlertModal', () => {
  test("should render 'StatusChangeAlertModal' component as expected", () => {
    const closeModal = jest.fn();
    const onSubmit = jest.fn();

    render(
      <StatusChangeAlertModal
        modalProps={{
          isOpen: true,
          onDismiss: closeModal,
        }}
        onSubmit={onSubmit}
      />,
    );
    expect(screen.getByText('Alert')).toBeInTheDocument();
    expect(
      screen.getByText(
        "Turning off the terminal will stop generating digital bills on this store's terminal.",
      ),
    ).toBeInTheDocument();
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
