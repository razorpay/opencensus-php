import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import * as Messages from 'merchant/views/onboarding/mobile/ActivationModals/Constant';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import ActivationFormModal from 'merchant/views/onboarding/mobile/ActivationModals/ActivationFormModals';
import { render, screen, cleanup, fireEvent, waitFor } from 'test-utils';

afterEach(() => {
  cleanup();
});

const App = ({ isOpen, modaltype }) => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <ActivationFormModal isOpen={isOpen} modalType={modaltype} closeModal={() => {}} />;
};

test('should open dedupe modal and show correct message', async () => {
  render(<App isOpen={true} modaltype="dedupe" />, {});
  await waitFor(() => {
    expect(screen.getByText(Messages.DEDUPE.title)).toBeInTheDocument();
    expect(screen.getByText(Messages.DEDUPE.description)).toBeInTheDocument();
    expect(screen.getByText(Messages.DEDUPE.buttonText)).toBeInTheDocument();
    fireEvent.click(screen.getByTestId('modalCloseButton'));
  });
});

test('should open Poi initiated modal and show correct message', () => {
  render(<App isOpen={true} modaltype="poi_initiated" />, {});
  expect(screen.getByText(Messages.POI_INITIATED.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.POI_INITIATED.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.POI_INITIATED.buttonText)).toBeInTheDocument();
  fireEvent.click(screen.getByText(Messages.POI_INITIATED.buttonText));
  //post modal close merchant go to home screen and wait untill get poi updated response.
});

test('should open Payment Enable modal and show correct message', () => {
  render(<App isOpen={true} modaltype="payment_enable" />, {});
  expect(screen.getByText(Messages.PAYMENT_ENABLE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.buttonText)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_ENABLE.secondryButtonText)).toBeInTheDocument();
  fireEvent.click(screen.getByText(Messages.PAYMENT_ENABLE.secondryButtonText));
});

test('should open Payment Disable modal and show correct message', () => {
  render(<App isOpen={true} modaltype="payment_disable" />, {});
  expect(screen.getByText(Messages.PAYMENT_DISABLE.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_DISABLE.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.PAYMENT_DISABLE.buttonText)).toBeInTheDocument();
  fireEvent.click(screen.getByText(Messages.PAYMENT_DISABLE.buttonText));
});

test('should open TNC generate modal and show correct message', () => {
  render(<App isOpen={true} modaltype="tnc" />, {});
  expect(screen.getByText(Messages.TNC.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.TNC.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.TNC.buttonText)).toBeInTheDocument();
  fireEvent.click(screen.getByText(Messages.TNC.buttonText));
});

test('should open underreview modal and show correct message', () => {
  render(<App isOpen={true} modaltype="under_review" />, {});
  expect(screen.getByText(Messages.UNDER_REVIEW.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.UNDER_REVIEW.description)).toBeInTheDocument();
  expect(screen.getByText('Back To Dashboard')).toBeInTheDocument();
  fireEvent.click(screen.getByText('Back To Dashboard'));
});

test('should open Needs clarification modal and show correct message', () => {
  render(<App isOpen={true} modaltype="needs_clarification" />, {});
  expect(screen.getByText(Messages.NC.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.NC.description.normal_nc)).toBeInTheDocument();
  expect(screen.getByText(Messages.NC.buttonText)).toBeInTheDocument();
  fireEvent.click(screen.getByText(Messages.NC.buttonText));
});

test('should open Rejected modal and show correct message', () => {
  render(<App isOpen={true} modaltype="rejected" />, {});
  expect(screen.getByText(Messages.REJECTED.title)).toBeInTheDocument();
  expect(screen.getByText(Messages.REJECTED.description)).toBeInTheDocument();
  expect(screen.getByText(Messages.REJECTED.buttonText)).toBeInTheDocument();
  fireEvent.click(screen.getByText(Messages.REJECTED.buttonText));
});

test('should not open if modal type empty', () => {
  render(<App isOpen={true} modaltype="" />, {});
  expect(screen.queryByText('modal should not be open')).not.toBeInTheDocument();
});

test('should show action required in case of needs clarification message which includes with payments and settlement enabled', () => {
  render(<App isOpen={true} modaltype="needs_clarification_payments_settlement_enabled" />, {});
  expect(screen.queryByText('ACTION REQUIRED')).toBeInTheDocument();
});

test('should show needs clarification messafe with payments enabled', () => {
  render(<App isOpen={true} modaltype="needs_clarification_with_payments_enabled" />, {});
  expect(
    screen.queryByText(
      'You’ll be able to receive collected payments in your account only after the required details are updated',
    ),
  ).toBeInTheDocument();
  expect(screen.queryByText('ACTION REQUIRED')).toBeInTheDocument();
});

test('should show needs clarification message with payments disabled', () => {
  render(<App isOpen={true} modaltype="needs_clarification_with_payment_disabled" />, {});
  expect(
    screen.queryByText(
      'You’ll be able to collect payments and receive them in your bank account only after the required details are updated',
    ),
  ).toBeInTheDocument();
  expect(screen.queryByText('ACTION REQUIRED')).toBeInTheDocument();
});
