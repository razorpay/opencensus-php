import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import {
  StartReferringStep,
  ActivateAccountStep,
  IntegratingAPIStep,
  CommissionStep,
} from 'merchant/views/PartnerDashboard/Home/Components/ActivationGuide/ActivationStepVariants';
import { createMemoryHistory } from 'history';

// TODO: only basic render test added, other tests can be added later.

const commonProps = {
  fuxStatus: {
    isFetching: false,
    value: {
      first_submerchant_added: true,
      first_earning_generated: false,
      first_commission_payout: false,
      api_integration: false,
      first_submerchant_accept_payments: true,
    },
  },
  partnerType: 'reseller',
};

let history;

describe('ActivationStepVariants', () => {
  beforeAll(() => {
    history = createMemoryHistory();
    history.push = jest.fn();
    window.cdnBaseUrl = 'https://razorpay-cdn.com';
  });

  describe('StartReferringStep', () => {
    const defaultProps = {
      ...commonProps,
      handleReferClient: jest.fn(),
      orgName: 'Razorpay',
      partnerName: 'dummy partner',
    };
    test('should render StartReferringStep', () => {
      render(<StartReferringStep {...defaultProps} />);
      expect(screen.getByText('Good Job dummy partner, Keep Referring')).toBeVisible();
    });
  });

  describe('ActivateAccountStep', () => {
    const defaultProps = {
      ...commonProps,
      activation_status: null,
      history,
      orgName: 'Razorpay',
      trackUserEvent: jest.fn(),
    };
    test('should render ActivateAccountStep', () => {
      render(<ActivateAccountStep {...defaultProps} />);
      expect(
        screen.getByText('Give us a few details and become eligible for commissions'),
      ).toBeVisible();
      expect(screen.getByRole('button', { name: 'Submit KYC' })).toBeVisible();
    });
  });

  describe('IntegratingAPIStep', () => {
    const defaultProps = {
      ...commonProps,
      activation_status: null,
      trackUserEvent: jest.fn(),
      orgName: 'Razorpay',
    };
    test('should render null for reseller', () => {
      render(<IntegratingAPIStep {...defaultProps} />);
      expect(screen.getByTestId('component-wrapper').firstChild).not.toBeInTheDocument();
    });
    test('should render for aggregator', () => {
      render(<IntegratingAPIStep {...defaultProps} partnerType="aggregator" />);
      expect(screen.getByText('Integrate using APIs')).toBeVisible();
    });
  });

  describe('CommissionStep', () => {
    const defaultProps = {
      ...commonProps,
      activation_status: null,
      history,
      orgName: 'Razorpay',
    };
    test('should render CommissionStep', () => {
      render(<CommissionStep {...defaultProps} />);
      expect(
        screen.getByText('Start earning by getting your clients to start using our products'),
      ).toBeVisible();
    });
  });
});
