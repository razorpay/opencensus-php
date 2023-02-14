import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import EnableWidget from 'merchant/views/Affordability/AffordabilityWidget/Onboarding/EnableWidget';
import { render, screen, userEvent } from 'test-utils';

const openModal = jest.fn();
describe('Validate Affordability widget enablement', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */

  beforeAll(() => {
    window.rzp_user = {};
    history.push = jest.fn();
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: () => ({
        success: jest.fn(),
        interaction: jest.fn(),
      }),
    };
  });

  const defaultProps = {
    updateFeatures: jest.fn(),
    showNotification: jest.fn(),
    updateWidgetStatus: jest.fn(),
    openModal,
    affordability: {
      pricing: {
        default: 100000,
      },
    },
  };

  const renderApp = (props = {}) => {
    render(<EnableWidget {...defaultProps} {...props} closeOnboarding={() => {}} />, {
      initialState: {
        session: { user: { isAffordabilityWidgetEnabled: true } },
      },
    });
  };

  test('should render Enable Widget component without error', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render Enable Widget CTA', () => {
    renderApp();
    expect(screen.getByText('Enable Widget')).toBeInTheDocument();
  });

  test('Should open enable widget poup', async () => {
    renderApp();
    const enableBtn = screen.getByRole('button', {
      name: 'Enable Widget',
    });
    await userEvent.click(enableBtn);
    expect(openModal).toHaveBeenCalled();
  });
});
