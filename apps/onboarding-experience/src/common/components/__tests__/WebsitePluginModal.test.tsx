import React from 'react';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import WebsitePluginModal, { TOP_THREE_PLUGINS } from '../WebsitePluginModal';

describe('WebsitePluginModal', () => {
  const mockOnDismiss = jest.fn();
  const mockHandleAddPlugin = jest.fn().mockResolvedValue(undefined);

  const mockSupportedPlugins = [
    { name: 'WordPress', icon: 'wordpress.svg' },
    { name: 'Magento', icon: 'magento.svg' },
    { name: 'Shopify', icon: 'shopify.svg' },
  ];

  const defaultProps = {
    onDismiss: mockOnDismiss,
    handleAddPlugin: mockHandleAddPlugin,
    supportedPlugins: mockSupportedPlugins,
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders modal with correct title and subtitle', () => {
    renderWithWrappers(<WebsitePluginModal {...defaultProps} />);

    expect(screen.getByText('Select your website plugin')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Every plugin has a different way of integration so make sure you select the right one.',
      ),
    ).toBeInTheDocument();
  });

  it('renders the top three plugins correctly', () => {
    renderWithWrappers(<WebsitePluginModal {...defaultProps} />);

    TOP_THREE_PLUGINS.forEach((plugin) => {
      const img = screen.getByAltText(plugin.alt);
      expect(img).toBeInTheDocument();
      expect(img).toHaveAttribute('src', plugin.src);
    });
  });

  it('renders the dropdown with supported plugins', () => {
    renderWithWrappers(<WebsitePluginModal {...defaultProps} />);

    const dropdownButton = screen.getByLabelText('Select other website plugins');
    fireEvent.click(dropdownButton);

    mockSupportedPlugins.forEach((plugin) => {
      expect(screen.getByText(plugin.name)).toBeInTheDocument();
    });
  });

  it('selects a plugin from the dropdown', () => {
    renderWithWrappers(<WebsitePluginModal {...defaultProps} />);

    // Open dropdown
    const dropdownButton = screen.getByLabelText('Select other website plugins');
    fireEvent.click(dropdownButton);

    // Select WordPress
    fireEvent.click(screen.getByText('WordPress'));

    // Check if Done button is enabled after selection
    const doneButton = screen.getByText('Done');
    expect(doneButton).not.toBeDisabled();
  });

  it('calls handleAddPlugin with selected plugin when Done is clicked', async () => {
    renderWithWrappers(<WebsitePluginModal {...defaultProps} />);

    // Open dropdown
    const dropdownButton = screen.getByLabelText('Select other website plugins');
    fireEvent.click(dropdownButton);

    // Select Shopify
    fireEvent.click(screen.getByText('Shopify'));

    // Click Done button
    const doneButton = screen.getByText('Done');
    fireEvent.click(doneButton);

    // Verify handleAddPlugin was called with the correct plugin name
    await waitFor(() => {
      expect(mockHandleAddPlugin).toHaveBeenCalledWith('Shopify');
    });
  });

  it('calls onDismiss after successful plugin addition', async () => {
    renderWithWrappers(<WebsitePluginModal {...defaultProps} />);

    // Open dropdown
    const dropdownButton = screen.getByLabelText('Select other website plugins');
    fireEvent.click(dropdownButton);

    // Select Shopify
    fireEvent.click(screen.getByText('Shopify'));

    // Click Done button
    const doneButton = screen.getByText('Done');
    fireEvent.click(doneButton);

    // Verify onDismiss was called after handleAddPlugin resolves
    await waitFor(() => {
      expect(mockOnDismiss).toHaveBeenCalled();
    });
  });
});
