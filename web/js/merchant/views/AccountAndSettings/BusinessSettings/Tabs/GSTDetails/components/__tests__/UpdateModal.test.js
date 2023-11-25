import React from 'react';
import UpdateModal from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/components/UpdateModal';
import { useMobile } from 'common/hooks/useMobile';
import { render, screen, userEvent, server } from 'test-utils';
import { mockGSTSubmit } from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/components/__tests__/mocks/fixtures/handlers';
import * as RequestActions from 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails/model';

jest.mock('common/hooks/useMobile', () => ({
  ...jest.requireActual('common/hooks/useMobile'),
  useMobile: jest.fn(),
}));

describe('UpdateModal component', () => {
  const App = ({ props }) => {
    return <UpdateModal {...props} />;
  };

  const submitGSTDetailsSpy = jest.spyOn(RequestActions, 'submitGSTDetails');

  describe('dWeb', () => {
    beforeEach(() => {
      useMobile.mockReturnValue(false);
      server.use(mockGSTSubmit);
      submitGSTDetailsSpy.mockClear();
    });

    test('should render modal content correctly', async () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1', '37AADCS0472N1X2'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />);

      const submitBtn = screen.getByRole('button', { name: 'Submit' });
      const input = screen.getByRole('combobox');

      expect(
        screen.getByText(
          'Your Business Address will also be updated to your GSTIN Registered Address',
        ),
      ).toBeInTheDocument();
      expect(submitBtn).toBeInTheDocument();
      expect(input).toBeInTheDocument();

      await userEvent.click(input);
      expect(screen.getByText(appProps.gstList[0])).toBeInTheDocument();
      expect(screen.getByText(appProps.gstList[1])).toBeInTheDocument();
    });

    test('should submit selected GST number', async () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1', '37AADCS0472N1X2'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />);

      const submitBtn = screen.getByRole('button', { name: 'Submit' });
      const input = screen.getByRole('combobox');

      expect(submitBtn).toBeInTheDocument();
      expect(input).toBeInTheDocument();

      await userEvent.click(input);
      expect(screen.getByText(appProps.gstList[0])).toBeInTheDocument();
      expect(screen.getByText(appProps.gstList[1])).toBeInTheDocument();

      await userEvent.click(screen.getByText(appProps.gstList[0]));
      await userEvent.click(submitBtn);

      expect(submitGSTDetailsSpy).toHaveBeenCalled();
    });
  });

  describe('mWeb', () => {
    beforeEach(() => {
      useMobile.mockReturnValue(true);
    });

    test('should render modal content correctly', () => {
      const appProps = {
        gstList: ['37AADCS0472N1Z1', '37AADCS0472N1X2'],
        defaultGSTIn: '29AAGCR4375J1ZU',
        setAlertStatus: jest.fn(),
      };
      render(<App props={appProps} />);

      const submitBtn = screen.getByRole('button', { name: 'Submit' });

      expect(
        screen.getByText(
          'Your Business Address will also be updated to your GSTIN Registered Address',
        ),
      ).toBeInTheDocument();
      expect(submitBtn).toBeInTheDocument();
      expect(screen.getByText(appProps.gstList[0])).toBeInTheDocument();
      expect(screen.getByText(appProps.gstList[1])).toBeInTheDocument();
    });
  });
});
