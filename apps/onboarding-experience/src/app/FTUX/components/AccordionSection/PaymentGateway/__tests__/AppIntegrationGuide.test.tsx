import React from 'react';
import { screen, render } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import AppIntegrationGuide from '../AppIntegrationGuide';
import { INTEGRATION_GUIDE } from '@FTUX/constants/accordion';

// Mock the SelectableOptionCard component
jest.mock('@OnboardingExperienceCommons/components/SelectableOptionCard', () => ({
  __esModule: true,
  default: ({
    customTitle,
    subTitle,
    cardImageUrl,
  }: {
    customTitle: React.ReactNode;
    subTitle: string;
    cardImageUrl: string;
  }) => (
    <div data-testid="selectable-option-card">
      {customTitle}
      <div data-testid="card-subtitle">{subTitle}</div>
      <img data-testid="card-image" src={cardImageUrl} alt="selectable option" />
    </div>
  ),
}));

// Mock the isMobileDevice function
jest.mock('@libs/shared-utils', () => ({
  isMobileDevice: jest.fn(),
}));

// Import the mocked function for testing
import { isMobileDevice } from '@libs/shared-utils';

describe('AppIntegrationGuide', () => {
  beforeEach(() => {
    // Reset all mocks before each test
    jest.clearAllMocks();
    // Default to desktop view
    (isMobileDevice as jest.Mock).mockReturnValue(false);
  });

  test('renders with correct heading and subtitle', () => {
    renderWithWrappers(<AppIntegrationGuide />);

    // Check the Resources for Apps heading
    expect(screen.getByText('Resources for Apps')).toBeInTheDocument();

    // Check the card subtitle
    expect(screen.getByTestId('card-subtitle')).toHaveTextContent('App integration');
  });

  test('shows Android integration guide link when hasAndroidIntent is true', () => {
    renderWithWrappers(<AppIntegrationGuide hasAndroidIntent={true} />);

    // Check that Android integration guide link is shown
    const androidLink = screen.getByText('Integration guide set up (Android)');
    expect(androidLink).toBeInTheDocument();
    expect(androidLink.closest('a')).toHaveAttribute('href', INTEGRATION_GUIDE['android']);
  });

  test('shows iOS integration guide link when hasIOSIntent is true', () => {
    renderWithWrappers(<AppIntegrationGuide hasIOSIntent={true} />);

    // Check that iOS integration guide link is shown
    const iOSLink = screen.getByText('Integration guide set up (iOS)');
    expect(iOSLink).toBeInTheDocument();
    expect(iOSLink.closest('a')).toHaveAttribute('href', INTEGRATION_GUIDE['ios']);
  });

  test('shows both Android and iOS integration guide links when both intents are true', () => {
    renderWithWrappers(<AppIntegrationGuide hasAndroidIntent={true} hasIOSIntent={true} />);

    // Check that both Android and iOS integration guide links are shown
    const androidLink = screen.getByText('Integration guide set up (Android)');
    const iOSLink = screen.getByText('Integration guide set up (iOS)');

    expect(androidLink).toBeInTheDocument();
    expect(androidLink.closest('a')).toHaveAttribute('href', INTEGRATION_GUIDE['android']);

    expect(iOSLink).toBeInTheDocument();
    expect(iOSLink.closest('a')).toHaveAttribute('href', INTEGRATION_GUIDE['ios']);
  });

  test('does not show any integration guide links when both intents are false', () => {
    renderWithWrappers(<AppIntegrationGuide hasAndroidIntent={false} hasIOSIntent={false} />);

    // Check that no integration guide links are shown
    expect(screen.queryByText('Integration guide set up (Android)')).not.toBeInTheDocument();
    expect(screen.queryByText('Integration guide set up (iOS)')).not.toBeInTheDocument();
  });

  test('renders with the detailed set up guide text', () => {
    renderWithWrappers(<AppIntegrationGuide hasAndroidIntent={true} />);

    // Check for the heading text
    expect(screen.getByText('Here is a detailed set up guide')).toBeInTheDocument();
  });

  test('renders with the WebsiteIntegratedIcon image', () => {
    renderWithWrappers(<AppIntegrationGuide />);

    // Check that the image is rendered
    const cardImage = screen.getByTestId('card-image');
    expect(cardImage).toBeInTheDocument();
    // The image should reference the WebsiteIntegratedIcon
    expect(cardImage).toHaveAttribute('alt', 'selectable option');
  });

  test('renders the SelectableOptionCard component correctly', () => {
    renderWithWrappers(<AppIntegrationGuide />);

    // Check that the SelectableOptionCard is rendered
    const card = screen.getByTestId('selectable-option-card');
    expect(card).toBeInTheDocument();
  });
});
