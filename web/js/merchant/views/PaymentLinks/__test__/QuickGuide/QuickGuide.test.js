/* eslint-disable babel/new-cap */
import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import QuickGuide from 'merchant/views/PaymentLinks/QuickGuide';
import { QuickGuideStep } from 'merchant/components/QuickGuide/QuickStepGuide';
import { render, screen } from 'test-utils';
import { getQuickGuideData } from 'merchant/views/PaymentLinks/QuickGuide/data';
import { quickGuideProps } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/fixtures';

describe('Payment Link QuickGuide', () => {
  const renderQuickGuideStep = (paymentLinkStatus = 'loading', quickStepState = 'PaymentLinks') => {
    let activeState = {};
    if (quickStepState === 'PaymentLinks') {
      activeState = { ...getQuickGuideData.PaymentLinks(paymentLinkStatus) };
    }
    if (quickStepState === 'ReceivePayments') {
      activeState = { ...getQuickGuideData.ReceivePayments(paymentLinkStatus) };
    }

    return render(
      <QuickGuideStep
        status={paymentLinkStatus}
        step="PaymentLink"
        feature="payment_links"
        {...activeState}
      />,
    );
  };

  const renderApp = ({ props } = {}) => {
    return render(<QuickGuide {...quickGuideProps('loading')} {...props} />);
  };

  test('should render QuickGuide component without errors', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render QuickGuideStep component without errors', () => {
    expect(renderQuickGuideStep).not.toThrowError();
  });

  test('should match quick guide payment link title in "pending" state', () => {
    renderQuickGuideStep('pending');
    expect(screen.getByText('1. Create Payment Link')).toBeInTheDocument();
  });

  test('should match quick guide payment link content', () => {
    renderQuickGuideStep('pending');
    expect(
      screen.getByText(
        'Create a payment link instantly and notify your customer via sms or email.',
      ),
    ).toBeInTheDocument();
  });

  test('should match quick guide payment link title', () => {
    renderQuickGuideStep('done');
    expect(screen.getByText('1. Payment link created')).toBeInTheDocument();
  });

  test('should match quick guide payment link content', () => {
    renderQuickGuideStep('done');
    expect(
      screen.getByText(
        'Create a payment link instantly and notify your customer via sms or email.',
      ),
    ).toBeInTheDocument();
  });

  test('should match quick guide recieve payment title when payment recieve status is pending', () => {
    renderQuickGuideStep('pending', 'ReceivePayments');
    expect(screen.getByText('2. Receive Payments')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your customers can make domestic and international payments directly on the payment link.',
      ),
    ).toBeInTheDocument();
  });

  test('should match quick guide recieve payment title', () => {
    renderQuickGuideStep('done', 'ReceivePayments');
    expect(screen.getByText('2. Payments Received')).toBeInTheDocument();
    expect(
      screen.getByText('You can check the payments you receive in the transactions.'),
    ).toBeInTheDocument();
  });

  test('should render GET STARTED link', () => {
    renderApp();
    expect(screen.getByText('GET STARTED')).toBeInTheDocument();
  });
});
