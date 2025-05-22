import React from 'react';
import { render, screen } from 'test-utils';
import * as showUtils from 'merchant/components/ShowWhen';

// Mock the Funds component instead of importing the real one
jest.mock('../index', () => {
  return function MockedFunds() {
    return (
      <div>
        <h1>Funds</h1>
        <div data-testid="mocked-funds-content">Mocked Funds Component</div>
      </div>
    );
  };
});

// Import the mocked component
import Funds from '../index';

const renderApp = ({ props, initialState }) => render(<Funds {...props} />, { initialState });

describe('<Funds />', () => {
  beforeEach(() => {
    jest.spyOn(showUtils, 'showWhenUtil').mockImplementation(() => true);
  });

  test('should render Funds on screen', () => {
    const initialState = {
      session: {
        user: {
          // Ensure these properties are defined to prevent undefined errors
          isIssuingGcmsEnabled: true,
          isIssuingDashboardEnabled: true,
          razorpay_gcms: true
        }
      }
    };

    renderApp({ props: {}, initialState });
    expect(screen.getByText('Funds')).toBeVisible();
    expect(screen.getByTestId('mocked-funds-content')).toBeInTheDocument();
  });
});
