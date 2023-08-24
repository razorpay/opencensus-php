import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { App } from 'merchant/views/Affordability/__test__/mocks/planDetails';
import { render, screen } from 'test-utils';

describe('Affordability self serve onboarding screen', () => {
  /*
   * @param {*} props = {}
   * @return <New /> component file
   */
  beforeAll(() => {
    window.rzp_user = {};

    window.rzpQ = {
      component: jest.fn(),
    };
  });

  const renderApp = (props = {}) => {
    render(<App {...props} closeOnboarding={() => {}} />, {
      initialState: {
        session: { user: { isAffordabilityWidgetEnabled: true } },
      },
    });
  };

  test('should render Onboarding component without error', () => {
    expect(renderApp).not.toThrowError();
  });

  // test('should load plan details screen', () => {
  //   renderApp();
  //   expect(screen.getByText('Plan Details')).toBeInTheDocument();
  // });

  test('should show offer nudge', () => {
    renderApp();
    expect(screen.getByText('Payment Offers')).toBeInTheDocument();
    expect(screen.getByText('Affordable Payment Options')).toBeInTheDocument();
  });

  test('Validate nudges redirection links', () => {
    renderApp();
    expect(screen.getAllByText('Enable Now')[0].href).toContain('/app/offers');
  });

  // test('should show special offer strip if discount', () => {
  //   const discountedPrice = {
  //     affordability: {
  //       ...planDetails.affordability,
  //       pricing: {
  //         rate: 2000,
  //         default: 4000,
  //       },
  //     },
  //   };
  //   renderApp({
  //     ...discountedPrice,
  //   });
  //   expect(screen.getByText('Special Offer!')).toBeInTheDocument();
  // });

  // test('should show disabled on if widget is disabled', () => {
  //   const disabled = {
  //     affordability: {
  //       ...planDetails.affordability,
  //       pricing: {
  //         rate: 2000,
  //         default: 4000,
  //       },
  //       lastAction: {
  //         name: 'DISABLE',
  //         createdTime: new Date().getTime(),
  //       },
  //       enabled: false,
  //     },
  //   };
  //   renderApp({
  //     ...disabled,
  //   });
  //   expect(screen.getByText('Disabled On')).toBeInTheDocument();
  // });
});
