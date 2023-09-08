import React from 'react';
import { Action } from 'history';
import { userEvent, render, screen, waitFor } from 'test-utils';

import { TogglePlanValue } from 'common/ui/PricingSubscription/PricingBundleCommon';
import { INITIAL_PLAN } from 'common/ui/PricingSubscription/PricingContext';
import { PAYMENT_TYPE } from 'common/ui/PricingSubscription/constants';
import CongratulatoryModal, {
  CongratulatoryModalContent,
  getBalanceMode,
} from 'common/ui/PricingSubscription/screen/Common/MultiPaymentOptions/CongratulatoryModal';
import {
  MODAL_TYPE,
  MODAL_CONTENT,
} from 'common/ui/PricingSubscription/screen/Common/MultiPaymentOptions/constant';

const CongratulatoryModalContentProps = {
  closeModal: jest.fn(),
  setCongratulatoryModal: jest.fn(),
  user: {
    isAllowedMultiple: jest.fn(),
    isAccountAndSettingsRevampEnabled: true,
  },
  type: MODAL_TYPE.SUFFICIENT_BALANCE,
  togglePlan: TogglePlanValue.monthly,
  selectedPaymentMode: PAYMENT_TYPE.INTERNAL,
  selectedPlan: INITIAL_PLAN,
  trackInstrumentation: jest.fn(),
  history: {
    length: 0,
    action: Action,
    location: {
      pathname: '',
      search: '',
      state: '',
      hash: '',
    },
    push: jest.fn(),
    replace: jest.fn(),
    go: jest.fn(),
    goBack: jest.fn(),
    goForward: jest.fn(),
    block: jest.fn(),
    listen: jest.fn(),
    createHref: jest.fn(),
  },
};

describe('Test: CongratulatoryModalContent component', () => {
  test('should render Modal content container', () => {
    render(<CongratulatoryModalContent {...CongratulatoryModalContentProps} />);
    const parentContainer = screen.getByTestId('modalContentContainer');
    expect(parentContainer).toBeInTheDocument();
  });
  test('should render modal title', () => {
    render(<CongratulatoryModalContent {...CongratulatoryModalContentProps} />);
    const parentContainer = screen.getByText('Congratulations!');
    expect(parentContainer).toBeInTheDocument();
  });
  test('should render modal `header` &  `subHeader` content for SUFFICIENT_BALANCE', () => {
    render(<CongratulatoryModalContent {...CongratulatoryModalContentProps} />);
    const type = 'SUFFICIENT_BALANCE';
    const modalHeaderContent = screen.getByTestId('modalMainHeader');
    const modalSubHeaderContent = screen.getByTestId('modalSubHeader');
    expect(modalHeaderContent).toBeInTheDocument();
    expect(modalSubHeaderContent).toBeInTheDocument();
    expect(modalHeaderContent.innerHTML).toBe(MODAL_CONTENT[type].header);
    expect(modalSubHeaderContent.innerHTML).toBe(MODAL_CONTENT[type].subHeader);
  });
  test('should render modal `header` &  `subHeader` content for INSUFFICIENT_BALANCE', () => {
    render(
      <CongratulatoryModalContent
        {...CongratulatoryModalContentProps}
        type="INSUFFICIENT_BALANCE"
      />,
    );
    const type = 'INSUFFICIENT_BALANCE';
    const modalHeaderContent = screen.getByTestId('modalMainHeader');
    const modalSubHeaderContent = screen.getByTestId('modalSubHeader');
    expect(modalHeaderContent).toBeInTheDocument();
    expect(modalSubHeaderContent).toBeInTheDocument();
    expect(modalHeaderContent.innerHTML).toBe(MODAL_CONTENT[type].header);
    expect(modalSubHeaderContent.innerHTML).toBe(MODAL_CONTENT[type].subHeader);
  });
  test('should render modal `header` &  `subHeader` content for CHECKOUT_PAYMENT', () => {
    render(
      <CongratulatoryModalContent {...CongratulatoryModalContentProps} type="CHECKOUT_PAYMENT" />,
    );
    const type = 'CHECKOUT_PAYMENT';
    const modalHeaderContent = screen.getByTestId('modalMainHeader');
    const modalSubHeaderContent = screen.getByTestId('modalSubHeader');
    expect(modalHeaderContent).toBeInTheDocument();
    expect(modalSubHeaderContent).toBeInTheDocument();
    expect(modalHeaderContent.innerHTML).toBe(MODAL_CONTENT[type].header);
    expect(modalSubHeaderContent.innerHTML).toBe(MODAL_CONTENT[type].subHeader);
  });
  test('should render modal button', async () => {
    render(<CongratulatoryModalContent {...CongratulatoryModalContentProps} />);
    const modalButton = screen.getByText('Check Pricing Plan Status');
    expect(modalButton).toBeInTheDocument();
    await userEvent.click(modalButton);
    expect(CongratulatoryModalContentProps.setCongratulatoryModal).toHaveBeenCalledWith(false);
  });
  test('should cover the both the condition of handleToastLink', async () => {
    const user = {
      isAllowedMultiple: () => true,
      isAccountAndSettingsRevampEnabled: true,
    };
    render(<CongratulatoryModalContent {...CongratulatoryModalContentProps} user={user} />);
    const modalButton = screen.getByText('Check Pricing Plan Status');
    expect(modalButton).toBeInTheDocument();
    await userEvent.click(modalButton);
    expect(CongratulatoryModalContentProps.setCongratulatoryModal).toHaveBeenCalledWith(false);
  });
  test('`getBalanceMode` should return correct balance type', () => {
    render(<CongratulatoryModalContent {...CongratulatoryModalContentProps} />);
    let balanceType = getBalanceMode(MODAL_TYPE.CHECKOUT_PAYMENT);
    expect(balanceType).toBe('Checkout payment');
    balanceType = getBalanceMode(MODAL_TYPE.INSUFFICIENT_BALANCE);
    expect(balanceType).toBe('Insufficient');
    balanceType = getBalanceMode(MODAL_TYPE.SUFFICIENT_BALANCE);
    expect(balanceType).toBe('Sufficient');
    balanceType = getBalanceMode('');
    expect(balanceType).toBe('');
  });
});
describe('Test: CongratulatoryModal component', () => {
  const mockCloseModal = jest.fn();
  const mockSetCongratulatoryModal = jest.fn();
  const mockHistory = { push: jest.fn() };
  const mockTrackInstrumentation = jest.fn();

  const mockProps = {
    closeModal: mockCloseModal,
    history: mockHistory,
    isCongModalOpen: true,
    setCongratulatoryModal: mockSetCongratulatoryModal,
    type: MODAL_TYPE.SUFFICIENT_BALANCE,
    selectedPlan: { id: 'planId', title: 'Plan Title' },
    selectedPaymentMode: jest.fn(),
    togglePlan: 'monthly',
    trackInstrumentation: mockTrackInstrumentation,
  };

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('calls handleClose and trackInstrumentation when modal is dismissed', async () => {
    render(<CongratulatoryModal {...mockProps} />);
    const dismissButton = screen.getByRole('button', {
      name: 'Close',
    });

    await userEvent.click(dismissButton);

    waitFor(() => {
      expect(mockSetCongratulatoryModal).toHaveBeenCalledWith(false);
    });
    expect(mockTrackInstrumentation).toHaveBeenCalledWith('', {
      event_method: 'initiated',
      cta_value: 'Close',
      toggle_switch: 'monthly',
      modal: 'Payment Success modal',
      plan_id: 'planId',
      plan_name: 'Plan Title',
      event_name: 'merchant_dashboard.click_close',
    });
  });

  test('calls trackInstrumentation on mount', () => {
    render(<CongratulatoryModal {...mockProps} />);

    expect(mockTrackInstrumentation).toHaveBeenCalledWith('', {
      event_method: 'initiated',
      balance: 'Sufficient',
      payment_method: 'Normal Checkout',
      plan_id: 'planId',
      plan_name: 'Plan Title',
      event_name: 'merchant_dashboard.payment_success_screen',
    });
  });
});
