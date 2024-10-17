import React from 'react';
import { render, screen } from 'apps/self-serve/src/services/test/test-utils';
import CardInfo from '../CardInfo';
import { Currency } from '../../../../Payments/types';

const defaultProps = {
  title: 'Card Info',
  value: 200,
  isAmount: false,
  subtitle: '',
  toolTipText: '',
  isLoading: false,
  currency: 'INR' as Currency,
  isLeader: false,
  isMobile: false,
};

describe('EntityAnalytics', () => {
  test('should show loader', () => {
    render(<CardInfo {...{ ...defaultProps, isLoading: true }} />);
    expect(screen.getByTestId('loading-shimmer')).toBeInTheDocument();
  });

  test('should render component for amount value', () => {
    render(<CardInfo {...{ ...defaultProps, isAmount: true }} />);
    expect(screen.getByText(defaultProps.title)).toBeInTheDocument();
  });

  test('should render component for variations of leader', () => {
    render(<CardInfo {...{ ...defaultProps, isLeader: true }} />);
    expect(screen.getByText(defaultProps.value)).toBeInTheDocument();
  });

  test('should render component for mobile component', () => {
    render(<CardInfo {...{ ...defaultProps, isMobile: true }} />);
    expect(screen.getByText(defaultProps.value)).toBeInTheDocument();
  });

  test('should render component isLeader is true', () => {
    render(<CardInfo {...{ ...defaultProps, isMobile: true, isLeader: true, isAmount: true }} />);
    expect(screen.getByText('2.00')).toBeInTheDocument();
  });
});
