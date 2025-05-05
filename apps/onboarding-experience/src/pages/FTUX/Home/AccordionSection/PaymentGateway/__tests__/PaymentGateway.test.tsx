import React from 'react';
import { screen } from 'apps/onboarding-experience/src/services/test/jest-utils';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import PaymentGateway from '../index';

// Mock the child components to isolate the PaymentGateway component testing
jest.mock('../InviteTeamMember', () => {
  return {
    __esModule: true,
    default: () => <div data-testid="invite-team-member">InviteTeamMember Component</div>,
  };
});

jest.mock('../IntegrationGuide', () => {
  return {
    __esModule: true,
    default: () => <div data-testid="integration-guide">IntegrationGuide Component</div>,
  };
});

jest.mock('../GenerateAPIKeys', () => {
  return {
    __esModule: true,
    default: () => <div data-testid="generate-api-keys">GenerateAPIKeys Component</div>,
  };
});

describe('PaymentGateway Component', () => {
  test('renders without crashing', () => {
    renderWithWrappers(<PaymentGateway />);

    // The component should render without errors
    expect(screen.getByTestId('invite-team-member')).toBeInTheDocument();
    expect(screen.getByTestId('integration-guide')).toBeInTheDocument();
    expect(screen.getByTestId('generate-api-keys')).toBeInTheDocument();
  });

  test('renders all child components in the correct order', () => {
    renderWithWrappers(<PaymentGateway />);

    const components = screen.getAllByTestId(/-team-member|-guide|-api-keys/);

    // Verify that all three components are rendered
    expect(components).toHaveLength(3);

    // Verify the order of components
    expect(components[0]).toHaveAttribute('data-testid', 'invite-team-member');
    expect(components[1]).toHaveAttribute('data-testid', 'integration-guide');
    expect(components[2]).toHaveAttribute('data-testid', 'generate-api-keys');
  });
});
