import React from 'react';

import { render, screen } from 'test-utils';

import ConsentPopup from '../Onboarding/ConsentPopup';
import { EXPORTER_REWARDS_LINKS } from '../constants';

const mockOnClose = jest.fn();

const renderApp = () => {
  render(<ConsentPopup isVisible={true} onClose={mockOnClose} />);
};

describe('ConsentPopup', () => {
  beforeEach(() => {
    renderApp();
  });

  test('displays modal information correctly', () => {
    const modalTexts = [
      'Terms of Use and Privacy Policy',
      'Rewards program for Cross Border Exporters',
      /To proceed, kindly confirm if you agree to our/,
      'I agree',
    ];

    modalTexts.forEach((text) => {
      expect(screen.getByText(text)).toBeInTheDocument();
    });
  });

  test('validates links within the modal', () => {
    const links = [
      { text: 'terms of use', href: EXPORTER_REWARDS_LINKS.TERMS },
      { text: 'privacy policy', href: EXPORTER_REWARDS_LINKS.PRIVACY_POLICY },
    ];

    links.forEach(({ text, href }) => {
      const linkElement = screen.getByText(text).closest('a');
      expect(linkElement).toHaveAttribute('href', href);
    });
  });
});
