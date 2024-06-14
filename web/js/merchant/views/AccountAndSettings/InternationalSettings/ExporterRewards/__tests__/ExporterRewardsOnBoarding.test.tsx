import React from 'react';

import { render, screen, userEvent } from 'test-utils';

import ExporterRewardsOnBoarding from '../Onboarding';
import { ONBOARDING_CARDS } from '../constants';

jest.mock('../Onboarding/ConsentPopup', () => ({
  __esModule: true,
  default: () => <>Terms of Use and Privacy Policy</>,
}));

const renderApp = () => {
  render(<ExporterRewardsOnBoarding />);
};

describe('ExporterRewardsOnBoarding', () => {
  beforeEach(() => {
    renderApp();
  });

  test('displays onboarding information correctly', () => {
    const onboardingText = screen.getByText('What makes Cross Border Exporter Rewards great?');
    expect(onboardingText).toBeInTheDocument();

    // Assert that there are exactly three headings and descriptions
    expect(screen.getAllByRole('heading')).toHaveLength(3);

    // Assert the text content of each feature
    ONBOARDING_CARDS.forEach((feature) => {
      expect(screen.getByText(feature.title)).toBeInTheDocument();
    });
  });

  test('assert "Get started" click', async () => {
    const button = screen.getByRole('button', { name: 'Get started' });
    expect(button).toBeInTheDocument();
    await userEvent.click(button);

    // Assert that the modal is visible
    expect(screen.getByText('Terms of Use and Privacy Policy')).toBeInTheDocument();
  });
});
