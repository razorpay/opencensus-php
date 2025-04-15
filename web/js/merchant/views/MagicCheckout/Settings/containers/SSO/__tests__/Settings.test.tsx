import React from 'react';
import { useNavigate } from 'react-router-dom';
import { render, screen, fireEvent, waitFor, within } from 'test-utils';
import Settings from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/tabs/Settings';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context';

jest.mock('merchant/views/MagicCheckout/Settings/containers/SSO/context', () => ({
  useSSOContext: jest.fn(),
}));

jest.mock('merchant/views/MagicCheckout/Settings/containers/SSO/api', () => ({
  updateMerchantTheme: jest.fn(),
  saveSSOSettings: jest.fn().mockResolvedValue({}),
}));

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: jest.fn(),
}));

const mockNavigate = jest.fn();
(useNavigate as jest.Mock).mockReturnValue(mockNavigate);

describe('Settings Component', () => {
  let mockContext: any;

  beforeEach(() => {
    mockContext = {
      isSSOEnabled: true,
      ssoWidget: {
        displayText: {
          heading: '',
          carousel: '',
        },
      },
      ssoSettings: {
        loginScreenOptions: [{ type: 'checkout_init', delay: 5, mandatory: true }],
        customerConsent: 'single',
        emailFlow: 'optional',
      },
      updateLoginScreenOptions: jest.fn(),
      updateCustomerConsent: jest.fn(),
      updateEmailFlow: jest.fn(),
      merchantId: '12345',
      isLoading: false,
    }; 

    (useSSOContext as jest.Mock).mockReturnValue(mockContext);
    (useNavigate as jest.Mock).mockReturnValue(mockNavigate);
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

    it('should render settings accordion component properly', async () => {
      render(<Settings />);

      // Open the accordion where the checkbox is
      const accordionButton1 = screen.getByRole('button', { name: /Where should we display login screen?/i });
      fireEvent.click(accordionButton1);

      const accordionButton2 = screen.getByRole('button', { name: /Ask consent from customer for marketing communication?/i });
      fireEvent.click(accordionButton2);

      const accordionButton3 = screen.getByRole('button', { name: /Collect missing emails from everyone?/i });
      fireEvent.click(accordionButton3);
    });

  it('should update selected options on checkbox change', async () => {
      render(<Settings />);

      // Expand the accordion if necessary
      const accordionButton = screen.getByRole('button', {
        name: /Where should we display login screen?/i
      });
      fireEvent.click(accordionButton);

      // Find checkbox by value
      const checkbox = screen.getAllByRole('checkbox').find(
        (checkbox) => checkbox.getAttribute('value') === 'checkout_init'
      );
      expect(checkbox).toBeInTheDocument();

      if (checkbox) {
        fireEvent.click(checkbox);
        expect(checkbox).not.toBeChecked();
      }
    });

    it('should call updateCustomerConsent when customer consent radio is changed', async () => {
        render(<Settings />);
        // Use label to query the radio button
        const separateRadio = screen.getByDisplayValue(/separate_selector/i);
        expect(separateRadio).toBeInTheDocument();
      
        fireEvent.click(separateRadio);
      
        await waitFor(() => {
          expect(mockContext.updateCustomerConsent).toHaveBeenCalled();
        });
      });

      it('should call handleSaveChanges on save button click', async () => {
        render(<Settings />);
      
        const saveButton = screen.getAllByRole('button').find(
          (button) => button.textContent?.toLowerCase().includes('save')
        );
        expect(saveButton).toBeInTheDocument();
      
        if (saveButton) {
          fireEvent.click(saveButton);
          await waitFor(() => {
            expect(mockContext.updateLoginScreenOptions).toHaveBeenCalled();
          });
        }
      });      

  it('should render buttons', async () => {
    render(<Settings />);
  
    const resetButton = screen.getAllByRole('button').find(
      (button) => button.textContent?.toLowerCase().includes('reset')
    );
    expect(resetButton).toBeInTheDocument();

    const saveButton = screen.getAllByRole('button').find(
        (button) => button.textContent?.toLowerCase().includes('save changes')
      );
      expect(saveButton).toBeInTheDocument();

      const nextButton = screen.getAllByRole('button').find(
        (button) => button.textContent?.toLowerCase().includes('next')
      );
      expect(nextButton).toBeInTheDocument();
  });  

  it('should navigate to next page on next button click', async () => {
    render(<Settings />);

    const nextButton = screen
      .getAllByRole('button')
      .find((button) => button.textContent?.toLowerCase().includes('next'));
    expect(nextButton).toBeInTheDocument();

    if (nextButton) {
      fireEvent.click(nextButton);

      await waitFor(() => {
        expect(mockNavigate).toHaveBeenCalledWith('/magic/settings/sso/customise');
      });
    }
  });
});
