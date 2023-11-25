import React from 'react';
import GSTDetails from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/GSTDetails';
import { render, screen, server, waitFor } from 'test-utils';
import {
  mockFetchGST,
  mockFetchGSTList,
} from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/__tests__/mocks/fixtures/handlers';
import { useMobile } from 'common/hooks/useMobile';
import { initialState } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/__tests__/mocks/fixtures/mocks';

jest.mock('common/hooks/useMobile', () => ({
  ...jest.requireActual('common/hooks/useMobile'),
  useMobile: jest.fn(),
}));

describe('GST details component', () => {
  const App = () => {
    return <GSTDetails />;
  };

  describe('dWeb - registered merchant', () => {
    beforeEach(() => {
      useMobile.mockReturnValue(false);
      server.use(mockFetchGSTList(), mockFetchGST());
      window.rzp_user = {};
    });
    test('should render content correctly', async () => {
      render(<App />, { initialState });

      expect(screen.getByText('GST details')).toBeInTheDocument();
      await waitFor(() => {
        expect(screen.getByText('GST Number')).toBeInTheDocument();
        expect(screen.getByText('29AAGCR4375J1E4')).toBeInTheDocument();
      });
      expect(screen.getByText(`Razorpay's GST Number`)).toBeInTheDocument();
      expect(screen.getByText('29AAGCR4375J1ZU')).toBeInTheDocument();
    });
  });

  describe('dWeb - unregistered merchant', () => {
    beforeEach(() => {
      useMobile.mockReturnValue(false);
      server.use(mockFetchGSTList(), mockFetchGST());
      window.rzp_user = {};
    });
    test('should render alert correctly', () => {
      const appState = { ...initialState };
      appState.session.user.isUnregisteredBusiness = true;
      render(<App />, { initialState: appState });

      expect(screen.getByText('GSTIN information')).toBeInTheDocument();
      expect(
        screen.getByText(
          'GST addition is not supported for your business type. You can create a new Razorpay Account as a Non- Individual business type and link GST to it.',
        ),
      ).toBeInTheDocument();
    });
  });
});
