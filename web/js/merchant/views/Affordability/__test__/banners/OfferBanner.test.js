import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { offerBanner } from 'merchant/views/Affordability/__test__/mocks/banners';
import { render, screen } from 'test-utils';
import SpecialOfferBanner from 'merchant/views/Affordability/components/banners/SpecialOfferBanner';
import OfferBanner from 'merchant/views/Affordability/components/banners/OfferBanner';

describe('Affordability self serve onboarding screen', () => {
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
  };

  const renderApp = (props = {}) => {
    render(
      <SpecialOfferBanner
        {...defaultProps}
        {...props}
        {...offerBanner}
        closeOnboarding={() => {}}
      />,
      {
        initialState: {
          session: { user: { isAffordabilityWidgetEnabled: true } },
        },
      },
    );
  };

  test('should render Onboarding component without erro', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render Special Offer Banner', () => {
    renderApp();
    expect(screen.getByText('🎉 Limited period offer')).toBeInTheDocument();
  });
});

describe('Affordability Self Serve Banner', () => {
  const renderApp = (props = {}) => {
    render(<OfferBanner {...props} {...offerBanner} closeOnboarding={() => {}} />, {
      initialState: {
        session: { user: { isAffordabilityWidgetEnabled: true } },
      },
    });
  };

  test('should render Offer Banner', () => {
    renderApp();
    expect(
      screen.getByText(
        'Start your free trial on Affordability Widget. No additional charges will be applied without your consent after the free trial.',
      ),
    ).toBeInTheDocument();
  });
});
