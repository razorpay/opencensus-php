import React from 'react';
import { FileIcon } from '@razorpay/blade/components';
import { render, screen, waitFor, userEvent } from 'test-utils';

import EditableCard from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/EditableCard';
import {
  WebsitePolicyPages,
  PolicyPagesSelection,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/types';

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils',
  () => {
    const originalModule = jest.requireActual(
      'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils',
    );
    return {
      __esModule: true,
      ...originalModule,
      getWebsiteCount: jest.fn(() => {
        return 0;
      }),
    };
  },
);

const defaultProps = {
  key: WebsitePolicyPages.TERMS,
  page: WebsitePolicyPages.TERMS,
  activeField: WebsitePolicyPages.TERMS,
  title: 'Terms and Conditions',
  radioValue: undefined, // yes|| no
  valid: 'none',
  value: '',
  Icon: FileIcon,
  setFormState: jest.fn(),
  onChange: jest.fn(),
  handleFocusOnNext: jest.fn(),
  setActiveField: jest.fn(),
};

const websiteLinkProps = {
  key: WebsitePolicyPages.TERMS,
  page: WebsitePolicyPages.TERMS,
  activeField: WebsitePolicyPages.TERMS,
  title: 'Terms and Conditions',
  radioValue: PolicyPagesSelection.YES, // yes|| no
  valid: 'none',
  value: '',
  Icon: FileIcon,
  setFormState: jest.fn(),
  onChange: jest.fn(),
  handleFocusOnNext: jest.fn(),
  setActiveField: jest.fn(),
};

const hostedByRzpProps = {
  key: WebsitePolicyPages.TERMS,
  page: WebsitePolicyPages.TERMS,
  activeField: undefined,
  title: 'Terms and Conditions',
  radioValue: PolicyPagesSelection.NO, // yes|| no
  valid: 'none',
  value: '',
  Icon: FileIcon,
  setFormState: jest.fn(),
  onChange: jest.fn(),
  handleFocusOnNext: jest.fn(),
  setActiveField: jest.fn(),
};

const notApplicableProps = {
  key: WebsitePolicyPages.SHIPPING,
  page: WebsitePolicyPages.SHIPPING,
  activeField: undefined,
  title: 'Shipping Policy',
  radioValue: PolicyPagesSelection.NA,
  valid: 'none',
  value: '',
  Icon: FileIcon,
  setFormState: jest.fn(),
  onChange: jest.fn(),
  handleFocusOnNext: jest.fn(),
  setActiveField: jest.fn(),
};

const renderApp = (props = defaultProps) => {
  const renderOutput = render(<EditableCard {...props} />);
  return renderOutput;
};

describe('Editable Card', () => {
  describe('Render radio buttons', () => {
    const formStateChange = jest.spyOn(defaultProps, 'setFormState');

    it('should render page card', () => {
      renderApp();
      expect(screen.getByText('Terms and Conditions')).toBeInTheDocument();
      expect(screen.getByText('Missing')).toBeInTheDocument();
    });

    it('should render page radio buttons', () => {
      renderApp();
      expect(screen.getByText('Yes I have the link for this')).toBeInTheDocument();
      expect(screen.getByText('No, create this page for me')).toBeInTheDocument();

      expect(screen.getByTestId('merchant-link')).toBeInTheDocument();
      expect(screen.getByTestId('create-via-rzp')).toBeInTheDocument();
    });

    it('should trigger radio onChange', async () => {
      renderApp();
      const linkRadioBtn = screen.getByTestId('merchant-link');
      await userEvent.click(linkRadioBtn);
      expect(formStateChange).toHaveBeenCalled();
    });
  });

  describe('Render Input box', () => {
    it('should render Input box for website', async () => {
      renderApp(websiteLinkProps as any);

      await waitFor(() => {
        expect(screen.getByTestId('webpage-link')).toBeInTheDocument();
        expect(
          screen.getByRole('button', { description: 'Save and continue' }),
        ).toBeInTheDocument();
      });
    });
  });

  describe('Render Created by rzp badge', () => {
    it('should render badge', async () => {
      renderApp(hostedByRzpProps as any);

      await waitFor(() => {
        expect(screen.getByText('Will be created by Razorpay')).toBeInTheDocument();
        expect(screen.getByText('Not Provided')).toBeInTheDocument();
      });
    });
  });

  describe('Not applicablle', () => {
    it('should render not applicabe option', () => {
      renderApp({
        ...notApplicableProps,
        activeField: WebsitePolicyPages.SHIPPING,
      } as any);

      expect(screen.getByText('Yes I have the link for this')).toBeInTheDocument();
      expect(screen.getByText('No, create this page for me')).toBeInTheDocument();
      expect(screen.getByText('NA')).toBeInTheDocument();

      expect(screen.getByTestId('merchant-link')).toBeInTheDocument();
      expect(screen.getByTestId('create-via-rzp')).toBeInTheDocument();
      expect(screen.getByTestId('not-applicable')).toBeInTheDocument();
    });

    it('should render not applicabe badge', async () => {
      renderApp(notApplicableProps as any);

      await waitFor(() => {
        expect(screen.getAllByText('Not Applicable').length).toBeGreaterThan(0);
      });
    });
  });
});
