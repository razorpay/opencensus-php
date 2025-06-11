import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import WebsitePluginModal, { TOP_THREE_PLUGINS } from '../WebsitePluginModal';
import { isMobileDevice } from '@libs/shared-utils';
import { act } from 'react-dom/test-utils';

// Mock the dependencies
jest.mock('@libs/shared-utils');
jest.mock('@razorpay/blade/components', () => ({
  Button: ({ children, isDisabled, isLoading, onClick, isFullWidth, ...props }: any) => (
    <button
      disabled={isDisabled}
      onClick={onClick}
      data-loading={isLoading}
      data-fullwidth={isFullWidth}
      {...props}
    >
      {children}
    </button>
  ),
  Box: ({ children, ...props }: any) => <div {...props}>{children}</div>,
  Text: ({ children, ...props }: any) => <span {...props}>{children}</span>,
  Card: ({ children, isSelected, onClick, testID, ...props }: any) => (
    <div data-selected={isSelected} onClick={onClick} data-testid={testID} {...props}>
      {children}
    </div>
  ),
  CardBody: ({ children, ...props }: any) => <div {...props}>{children}</div>,
  Dropdown: ({ children, ...props }: any) => <div {...props}>{children}</div>,
  DropdownOverlay: ({ children, ...props }: any) => <div {...props}>{children}</div>,
  AutoComplete: ({ placeholder, value, onChange, ...props }: any) => (
    <input
      placeholder={placeholder}
      value={value}
      onChange={(e) => onChange?.({ values: [e.target.value] })}
      data-testid="plugin-search"
      {...props}
    />
  ),
  ActionList: ({ children, ...props }: any) => <ul {...props}>{children}</ul>,
  ActionListItem: ({ title, value, ...props }: any) => (
    <li data-value={value} onClick={() => props.onChange?.({ values: [value] })} {...props}>
      {title}
    </li>
  ),
  ActionListItemAsset: ({ src, alt, ...props }: any) => <img src={src} alt={alt} {...props} />,
  SearchIcon: () => <span>🔍</span>,
}));

jest.mock('@libs/shared-ui', () => ({
  useModalComponents: () => ({
    Modal: ({ children, isOpen, onDismiss, ...props }: any) => (
      <div data-testid="modal" data-open={isOpen} {...props}>
        {children}
      </div>
    ),
    ModalHeader: ({ title, subtitle, ...props }: any) => (
      <div data-testid="modal-header" {...props}>
        <h2>{title}</h2>
        <p>{subtitle}</p>
      </div>
    ),
    ModalBody: ({ children, ...props }: any) => (
      <div data-testid="modal-body" {...props}>
        {children}
      </div>
    ),
    ModalFooter: ({ children, ...props }: any) => (
      <div data-testid="modal-footer" {...props}>
        {children}
      </div>
    ),
  }),
}));

// Mock the Zustand store
jest.mock('@federated/apps/shell/commonStore', () => ({
  useStore: jest.fn().mockImplementation((selector) =>
    selector({
      showNotification: jest.fn(),
    }),
  ),
}));

describe('WebsitePluginModal', () => {
  const mockOnDismiss = jest.fn();
  const mockHandleAddPlugin = jest.fn().mockResolvedValue(undefined);
  const mockShowNotification = jest.fn();
  const mockSupportedPlugins = [
    { name: 'Shopify', icon: 'shopify-icon.svg' },
    { name: 'WooCommerce', icon: 'woocommerce-icon.svg' },
    { name: 'Wix', icon: 'wix-icon.svg' },
    { name: 'Magento', icon: 'magento-icon.svg' },
    { name: 'PrestaShop', icon: 'prestashop-icon.svg' },
  ];

  beforeEach(() => {
    jest.clearAllMocks();
    (isMobileDevice as jest.Mock).mockReturnValue(false);
    console.error = jest.fn();

    // Mock useStore to return showNotification
    require('@federated/apps/shell/commonStore').useStore.mockImplementation((selector: any) =>
      selector({ showNotification: mockShowNotification }),
    );
  });

  it('renders modal with correct title and subtitle', () => {
    render(
      <WebsitePluginModal
        onDismiss={mockOnDismiss}
        handleAddPlugin={mockHandleAddPlugin}
        supportedPlugins={mockSupportedPlugins}
      />,
    );

    expect(screen.getByText('Select your website plugin')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Every plugin has a different way of integration so make sure you select the right one.',
      ),
    ).toBeInTheDocument();
  });

  it('renders the top three plugin cards', () => {
    render(
      <WebsitePluginModal
        onDismiss={mockOnDismiss}
        handleAddPlugin={mockHandleAddPlugin}
        supportedPlugins={mockSupportedPlugins}
      />,
    );

    // Check if all three top plugins are rendered
    for (const plugin of TOP_THREE_PLUGINS) {
      expect(screen.getByTestId(`plugin-card-${plugin.pluginName}`)).toBeInTheDocument();
      expect(screen.getByAltText(plugin.alt)).toBeInTheDocument();
    }
  });

  it('allows searching for plugins', async () => {
    render(
      <WebsitePluginModal
        onDismiss={mockOnDismiss}
        handleAddPlugin={mockHandleAddPlugin}
        supportedPlugins={mockSupportedPlugins}
      />,
    );

    // Find the search input
    const searchInput = screen.getByTestId('plugin-search');

    // Type a plugin name
    await act(async () => {
      fireEvent.change(searchInput, { target: { value: 'Magento' } });
    });

    // Now the Select button should be enabled
    const selectButton = screen.getByRole('button', { name: 'Select' });
    expect(selectButton).not.toBeDisabled();
  });

  it('calls handleAddPlugin with selected plugin when Select is clicked', async () => {
    render(
      <WebsitePluginModal
        onDismiss={mockOnDismiss}
        handleAddPlugin={mockHandleAddPlugin}
        supportedPlugins={mockSupportedPlugins}
      />,
    );

    // Select a plugin
    await act(async () => {
      fireEvent.click(screen.getByTestId('plugin-card-Shopify'));
    });

    // Click the Select button
    const selectButton = screen.getByRole('button', { name: 'Select' });
    await act(async () => {
      fireEvent.click(selectButton);
    });

    // Verify handleAddPlugin was called with the correct plugin
    await waitFor(() => {
      expect(mockHandleAddPlugin).toHaveBeenCalledWith('Shopify');
    });

    // Verify onDismiss was called
    expect(mockOnDismiss).toHaveBeenCalled();
  });

  it('renders mobile version correctly', async () => {
    // Mock mobile device
    (isMobileDevice as jest.Mock).mockReturnValue(true);

    render(
      <WebsitePluginModal
        onDismiss={mockOnDismiss}
        handleAddPlugin={mockHandleAddPlugin}
        supportedPlugins={mockSupportedPlugins}
      />,
    );

    // Select a plugin
    await act(async () => {
      fireEvent.click(screen.getByTestId('plugin-card-Shopify'));
    });

    // Verify that the button has fullWidth attribute in mobile mode
    const selectButton = screen.getByRole('button', { name: 'Select' });
    expect(selectButton).toHaveAttribute('data-fullwidth', 'true');
  });

  it('shows error notification when plugin selection fails', async () => {
    const errorMessage = 'Failed to select plugin';
    mockHandleAddPlugin.mockRejectedValueOnce(new Error(errorMessage));

    render(
      <WebsitePluginModal
        onDismiss={mockOnDismiss}
        handleAddPlugin={mockHandleAddPlugin}
        supportedPlugins={mockSupportedPlugins}
      />,
    );

    // Select a plugin
    await act(async () => {
      fireEvent.click(screen.getByTestId('plugin-card-Shopify'));
    });

    // Click the Select button
    const selectButton = screen.getByRole('button', { name: 'Select' });
    await act(async () => {
      fireEvent.click(selectButton);
    });

    // Verify showNotification was called with the error message
    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'error',
        message: errorMessage,
      });
    });

    // Verify onDismiss was not called
    expect(mockOnDismiss).not.toHaveBeenCalled();
  });

  it('shows generic error notification when no specific error message is provided', async () => {
    // Reject with something that is not an Error object
    mockHandleAddPlugin.mockRejectedValueOnce('Some error occurred');

    render(
      <WebsitePluginModal
        onDismiss={mockOnDismiss}
        handleAddPlugin={mockHandleAddPlugin}
        supportedPlugins={mockSupportedPlugins}
      />,
    );

    // Select a plugin
    await act(async () => {
      fireEvent.click(screen.getByTestId('plugin-card-Shopify'));
    });

    // Click the Select button
    const selectButton = screen.getByRole('button', { name: 'Select' });
    await act(async () => {
      fireEvent.click(selectButton);
    });

    // Verify showNotification was called with the generic error message
    await waitFor(() => {
      expect(mockShowNotification).toHaveBeenCalledWith({
        type: 'error',
        message: 'An error occurred while selecting the plugin!',
      });
    });

    // Verify onDismiss was not called
    expect(mockOnDismiss).not.toHaveBeenCalled();
  });
});
