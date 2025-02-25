import React from 'react';
import { screen } from '@testing-library/react';

import { render } from 'test-utils';

import CreateOfferWizard from '../index';

describe('CreateOfferWizard Component', () => {
  beforeAll(() => {
    window.rzpQ = window.rzpQ || {};
    window.rzpQ.component = window.rzpQ.component || jest.fn();
  });

  afterAll(() => {
    delete window.rzpQ;
  });

  test('renders template cards correctly', () => {
    render(<CreateOfferWizard />);
    expect(screen.getByText('Pick a promotion type')).toBeInTheDocument();
    expect(screen.getByText('Discounts & Cash Backs')).toBeInTheDocument();
    expect(screen.getByText('Offers on Subscriptions')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Provide discounts on one or more auto-debit payments during a subscription cycle.',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Create offers for discounts or cash-backs to your customers when they make a payment.',
      ),
    ).toBeInTheDocument();
    expect(screen.getAllByText('Create Now').length).toBe(2);
  });

  test('renders OfferTypeSelector component when showSelectionView is true', () => {
    render(<CreateOfferWizard />);
    const offerTypeSelector = screen.getByTestId('component-wrapper');
    expect(offerTypeSelector).toBeInTheDocument();
  });

  test('renders OfferTypeSelector component when showSelectionView is true', () => {
    render(<CreateOfferWizard />);
    const offerTypeSelector = screen.getByTestId('component-wrapper');
    expect(offerTypeSelector).toBeInTheDocument();
  });

  test('renders the correct modal structure', () => {
    render(<CreateOfferWizard />);
    const modal = screen.getByTestId('component-wrapper');
    expect(modal).toBeInTheDocument();
    expect(modal.querySelector('.StandAloneContainer')).toBeInTheDocument();
    expect(modal.querySelector('.Offers--TypeSelection')).toBeInTheDocument();
    expect(modal.querySelector('.slide-in .heading')).toHaveTextContent('Pick a promotion type');
  });

  test('renders layerHost div correctly', () => {
    render(<CreateOfferWizard />);
    const layerHost = screen.getByTestId('layerTestId');
    expect(layerHost).toBeInTheDocument();
    expect(layerHost).toHaveAttribute('id', 'layerHost');
  });

  test('renders ReactModalPortal div', () => {
    render(<CreateOfferWizard />);
    const reactModalPortal = document.querySelector('.ReactModalPortal');
    expect(reactModalPortal).toBeInTheDocument();
  });
});
