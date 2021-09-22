import ModalCloseReasons from 'merchant/views/Settlements/Settlements/components/Modals/ModalCloseReasons';
import React from 'react';
import { fireEvent, render, screen, waitFor } from 'test-utils';
import { CLOSE_OPTIONS } from 'merchant/views/Settlements/Settlements/data';
import TestModal from 'common/services/test/TestModal';

const mockGoBackToInitialModalView = jest.fn();

function App() {
  return (
    <TestModal
      component={
        <ModalCloseReasons
          eventCategory="Dashboard - Early Settlement"
          closeOrigin="Scheduled"
          goBackToInitialModalView={mockGoBackToInitialModalView}
        />
      }
    />
  );
}

test('should display all close reasons', () => {
  render(<App />, { showModal: true });

  for (let i = 0; i < CLOSE_OPTIONS.length; i++) {
    expect(screen.getByText(new RegExp(CLOSE_OPTIONS[i].label))).toBeInTheDocument();
  }
});

test('should disable confirm button if no option is selected', () => {
  render(<App />, { showModal: true });

  expect(screen.getByRole('button', { name: 'Confirm & Close' })).toBeDisabled();
});

test('should select an option on click', () => {
  render(<App />, { showModal: true });

  const labelRadio = screen.getByLabelText(CLOSE_OPTIONS[0].label) as HTMLInputElement;

  expect(labelRadio.checked).toEqual(false);
  fireEvent.click(labelRadio);
  expect(labelRadio.checked).toEqual(true);
});

test('should type on write a bried textarea', () => {
  render(<App />, { showModal: true });

  const writeABriefArea = screen.getByRole('textbox') as HTMLInputElement;

  fireEvent.change(writeABriefArea, {
    target: { value: 'Houston, we have a problem.' },
  });

  expect(screen.getByText(/Houston, we have a problem/)).toBeInTheDocument();
});

test('should trigger onGoBack on clicking Go Back and close modal', async () => {
  render(<App />, { showModal: true });

  fireEvent.click(screen.getByText(/Go Back/i));

  await waitFor(() => expect(mockGoBackToInitialModalView).toBeCalledTimes(1));

  for (let i = 0; i < CLOSE_OPTIONS.length; i++) {
    expect(screen.queryByText(new RegExp(CLOSE_OPTIONS[i].label))).not.toBeInTheDocument();
  }
});

test('should trigger analytics call on submitting', () => {
  render(<App />, { showModal: true });

  const labelRadio = screen.getByLabelText(CLOSE_OPTIONS[0].label) as HTMLInputElement;
  fireEvent.click(labelRadio);

  fireEvent.click(screen.getByRole('button', { name: 'Confirm & Close' }));

  // 3 events, for trackEsChurnReason, trackEsModalCloseAction and a direct call
  expect(window.rzpAnalytics).toBeCalledTimes(3);

  for (let i = 0; i < CLOSE_OPTIONS.length; i++) {
    expect(screen.queryByText(new RegExp(CLOSE_OPTIONS[i].label))).not.toBeInTheDocument();
  }
});
