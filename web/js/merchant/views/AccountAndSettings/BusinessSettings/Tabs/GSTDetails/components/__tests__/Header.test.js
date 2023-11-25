import React from 'react';
import Header from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/components/Header';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { useMobile } from 'common/hooks/useMobile';
import * as ModalActions from 'merchant_common/reducers/modals';

jest.mock('common/hooks/useMobile', () => ({
  ...jest.requireActual('common/hooks/useMobile'),
  useMobile: jest.fn(),
}));

jest.mock('merchant/components/ShowWhen', () => ({
  ...jest.requireActual('merchant/components/ShowWhen'),
  __esModule: true,
  default: ({ children }) => <>{children}</>,
}));

describe('GST - Header', () => {
  const App = ({ props }) => {
    return <Header {...props} />;
  };

  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  describe('dWeb', () => {
    beforeEach(() => {
      useMobile.mockReturnValue(false);
      openModalSpy.mockClear();
    });
    test('should render header correctly', () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />);

      expect(screen.getByText('GST details')).toBeInTheDocument();
    });

    test('should render update CTA', () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />);

      expect(screen.getByText('Update GST details')).toBeInTheDocument();
    });

    test('should open GST modal', async () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />, { showModal: true });

      const btn = screen.getByRole('button', { name: 'Update GST details' });
      expect(btn).toBeInTheDocument();
      await userEvent.click(btn);
      expect(openModalSpy).toHaveBeenCalledTimes(1);
      await waitFor(() => {
        expect(screen.getByText('Edit GST detail')).toBeInTheDocument();
      });
    });
  });

  describe('mWeb', () => {
    beforeEach(() => {
      useMobile.mockReturnValue(true);
    });
    test('should render header correctly', () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />);

      expect(screen.getByText('GST details')).toBeInTheDocument();
    });

    test('should render update CTA', () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />);

      expect(screen.getByText('Update GST details')).toBeInTheDocument();
    });

    test('should open GST modal', async () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />, { showModal: true });

      const btn = screen.getByRole('button', { name: 'Update GST details' });
      expect(btn).toBeInTheDocument();
      await userEvent.click(btn);
      expect(openModalSpy).toHaveBeenCalledTimes(1);
      await waitFor(() => {
        expect(screen.getByText('Edit GST detail')).toBeInTheDocument();
      });
    });
  });
});
