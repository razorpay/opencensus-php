import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import EnableWidget from 'merchant/views/Affordability/AffordabilityWidget/Onboarding/EnableConfirmModal';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { affordabilityFeaturesMapping } from 'merchant/views/Affordability/AffordabilityWidget/Onboarding/data';

const openModal = jest.fn();
const showNotification = jest.fn();
const onConfirm = jest.fn();
describe('Validate Affordability widget enablement', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */

  beforeAll(() => {
    window.rzp_user = {
      aff_features: [
        {
          feature: 'affordability_widget',
          value: false,
        },
        {
          feature: 'affordability_widget_set',
          value: true,
        },
      ],
    };
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
    updateWidgetStatus: jest.fn(),
    showNotification,
    openModal,
    onConfirm: jest.fn(),
    pricing: {
      default: 100000,
    },
    mode: 'test',
    user: { isAffordabilityWidgetEnabled: true },
  };

  const renderApp = (props = {}) => {
    render(<EnableWidget {...defaultProps} {...props} closeOnboarding={() => {}} />, {
      initialState: {
        session: { user: { isAffordabilityWidgetEnabled: true }, mode: 'test' },
      },
    });
  };

  test('should render Enable Widget component without error', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render Enable Widget CTA', () => {
    renderApp();
    expect(screen.getByText('Yes, enable')).toBeInTheDocument();
  });

  test('Validate Account activated widget enablement', async () => {
    renderApp();
    const enableBtn = screen.getByRole('button', {
      name: 'Yes, enable',
    });
    await userEvent.click(enableBtn);
    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: 'Please activate your Razorpay account to enable the widget.',
      });
    });
  });

  test('Validate user role widget enablement', async () => {
    renderApp({
      user: {
        merchant: {
          activated: true,
        },
      },
    });
    const enableBtn = screen.getByRole('button', {
      name: 'Yes, enable',
    });
    await userEvent.click(enableBtn);
    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: "You don't have suffiecient permission to enable the affordability widget",
      });
    });
  });

  test('Validate test mode widget enablement', async () => {
    renderApp({
      user: {
        merchant: {
          activated: true,
        },
        role: 'admin',
      },
      mode: 'test',
    });
    const enableBtn = screen.getByRole('button', {
      name: 'Yes, enable',
    });
    await userEvent.click(enableBtn);
    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: 'Please enable live mode to enable affordability widget',
      });
    });
  });

  test('Validate live mode widget enablement', async () => {
    renderApp({
      user: {
        merchant: {
          activated: true,
        },
        role: 'admin',
      },
      mode: 'live',
    });
    const enableBtn = screen.getByRole('button', {
      name: 'Yes, enable',
    });
    await userEvent.click(enableBtn);
    await waitFor(() => {
      expect(showNotification).not.toHaveBeenCalledWith({
        type: 'error',
        message: 'Please enable live mode to enable affordability widget',
      });
    });
  });
});

describe('Validate Widget Enablement Flags', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */

  beforeAll(() => {
    window.rzp_user = {
      aff_features: [
        {
          feature: 'affordability_widget',
          value: false,
        },
        {
          feature: 'affordability_widget_set',
          value: true,
        },
      ],
    };
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
    updateWidgetStatus: jest.fn(),
    showNotification,
    openModal,
    onConfirm,
    pricing: {
      default: 100000,
    },
    mode: 'test',
    user: { isAffordabilityWidgetEnabled: true },
  };

  const renderApp = (props = {}) => {
    render(<EnableWidget {...defaultProps} {...props} closeOnboarding={() => {}} />, {
      initialState: {
        session: { user: { isAffordabilityWidgetEnabled: true }, mode: 'live' },
      },
    });
  };

  test('should render Enable Widget component without error', () => {
    expect(renderApp).not.toThrowError();
  });

  test('Validate onconfirm called with expected flags', async () => {
    window.rzp_user = {
      aff_features: [
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET,
          value: false,
        },
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET,
          value: true,
        },
      ],
    };
    renderApp({
      user: {
        merchant: {
          activated: true,
        },
        role: 'admin',
      },
      mode: 'live',
    });
    const enableBtn = screen.getByRole('button', {
      name: 'Yes, enable',
    });
    await userEvent.click(enableBtn);
    await waitFor(() => {
      expect(onConfirm).toHaveBeenCalledWith({
        features: { affordability_widget: true },
        should_sync: 0,
      });
    });

    window.rzp_user = {
      aff_features: [
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET,
          value: true,
        },
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET,
          value: false,
        },
      ],
    };

    await userEvent.click(enableBtn);
    await waitFor(() => {
      expect(onConfirm).toHaveBeenCalledWith({
        features: { affordability_widget_set: true },
        should_sync: 0,
      });
    });

    window.rzp_user = {
      aff_features: [
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET,
          value: false,
        },
        {
          feature: affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET,
          value: false,
        },
      ],
    };

    await userEvent.click(enableBtn);
    await waitFor(() => {
      expect(onConfirm).toHaveBeenCalledWith({
        features: { affordability_widget: true, affordability_widget_set: true },
        should_sync: 0,
      });
    });
  });
});
