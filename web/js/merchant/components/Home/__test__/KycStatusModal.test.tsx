import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import KYCStatusModal from 'merchant/components/Home/KYCStatusModal';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';
import { MOCK_USER_FOR_POS_NC } from 'merchant/components/Home/__test__/mocks/fixtures';

const initProps = {
  onClose: jest.fn(),
  closeModal: jest.fn(),
  openModal: jest.fn(),
  onGoToDashboard: jest.fn(),
  user: MOCK_USER_FOR_POS_NC,
  modalType: 'needs_clarification_for_pos',
  activationDuration: 100000,
  tracking: jest.fn(),
  showProductsModal: false,
  hideProductsModal: true,
  showProducts: false,
  trackEvents: jest.fn(),
  isNcEligibile: true,
};

const App = () => {
  const { status } = useActivation();
  if (status === 'loading') return <div>Loading...</div>;
  return <KYCStatusModal {...initProps} />;
};

const waitForLoadingToFinish = () => waitForElementToBeRemoved(screen.queryByText('Loading...'));

test('should show needs clarification message for pos', async () => {
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(
    screen.getByText('We need a few clarifications to complete POS KYC verification'),
  ).toBeInTheDocument();
  expect(screen.getByText('ACTION REQUIRED')).toBeInTheDocument();
});
