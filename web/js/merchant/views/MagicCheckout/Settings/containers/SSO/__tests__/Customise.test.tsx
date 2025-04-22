import React from 'react';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import Customise from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/tabs/Customise';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/index';
import { saveSSOSettings } from 'merchant/views/MagicCheckout/Settings/containers/SSO/api';

jest.mock('merchant/views/MagicCheckout/Settings/containers/SSO/context/index', () => ({
  useSSOContext: jest.fn(),
}));

jest.mock('merchant/views/MagicCheckout/Settings/containers/SSO/api', () => ({
  saveSSOSettings: jest.fn(),
}));

const mockUpdateSSOWidgetCarousel = jest.fn();
const mockUpdateSSOWidgetCss = jest.fn();

describe('Customise Component', () => {
  beforeEach(() => {
    (useSSOContext as jest.Mock).mockReturnValue({
      isSSOEnabled: false,
      ssoSettings: {
        loginScreenOptions: [{ type: 'landing_page', delay: 2, mandatory: false }],
        customerConsent: 'single_selector',
        emailFlow: 'collect_missing_email_flow',
      },
      ssoWidget: {
        backgroundColor: '#c89d32',
        buttonColor: '#1b0f04',
        fontFamily: 'Tasa',
        displayText: {
          heading: 'Hello again, snoozer! \n Exciting offer waiting for you',
          carousel: [
            { icon: '🎉', text: 'Enjoy hassle-free shopping with the best offers applied for you' },
            { icon: '🤩', text: 'Explore unbeatable prices and unmatchable value' },
            { icon: '🛡️', text: '100% secure & spam free, we will not annoy you, pinky promise!' },
          ],
        },
      },
      apiKey: 'test-api-key',
      merchantId: 'test-merchant-id',
      updateSSOWidgetCarousel: mockUpdateSSOWidgetCarousel,
      updateSSOWidgetCss: mockUpdateSSOWidgetCss,
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
    jest.resetModules();
  });

  it('should render the Customise component correctly', () => {
    render(<Customise />);
    expect(screen.getByText('Edit Mode')).toBeInTheDocument();
  });

  it('should handle save changes correctly', async () => {
    (saveSSOSettings as jest.Mock).mockResolvedValue({ success: true });

    render(<Customise />);

    const saveButton = screen.getByRole('button', { name: /Save/i });
    fireEvent.click(saveButton);

    await waitFor(() => {
      expect(saveSSOSettings).toHaveBeenCalledTimes(1);
    });
  });

  // Test for toggling between Edit Mode and Live Mode
  it('should toggle between edit and live mode', () => {
    render(<Customise />);

    // Initial state should be "Edit Mode"
    expect(screen.getByText('Edit Mode')).toBeInTheDocument();

    // Find and click the edit mode button
    const editModeButton = screen.getByText('Edit Mode').closest('div');
    fireEvent.click(editModeButton!);

    // Should now show "Live Mode"
    expect(screen.getByText('Live Mode')).toBeInTheDocument();

    // Find and click the live mode button
    const liveModeButton = screen.getByText('Live Mode').closest('div');
    fireEvent.click(liveModeButton!);

    // Should now show "Edit Mode"
    expect(screen.getByText('Edit Mode')).toBeInTheDocument();
  });

  // Test for changing background color, button color, and font
  it('should update widget background color, button color, and font', async () => {
    render(<Customise />);

    // Change background color
    const bgColorInput = screen.getByDisplayValue('#c89d32');
    fireEvent.change(bgColorInput, { target: { value: '#ffffff' } });

    // Change button color
    const buttonColorInput = screen.getByDisplayValue('#1b0f04');
    fireEvent.change(buttonColorInput, { target: { value: '#000000' } });

    // Open font dropdown
    fireEvent.click(screen.getByText('Change Font'));
    const fontOption = screen.getAllByText('Tasa')[0];
    fireEvent.click(fontOption);

    await waitFor(() => {
      expect(mockUpdateSSOWidgetCss).toHaveBeenCalledWith({
        backgroundColor: '#ffffff',
        buttonColor: '#000000',
        fontFamily: 'Tasa',
      });
    });
  });

  // Test for initializing SSO iframe correctly
  it('should initialize SSO iframe when rendered', () => {
    render(<Customise />);

    const iframe = screen.getByTestId('sso-iframe');
    expect(iframe).toHaveAttribute('src', expect.stringContaining('test-api-key'));
  });
});
