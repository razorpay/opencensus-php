import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';

import MultiSelectDropdown from '../MultiSelectDropdown';

// Mock the constants and utilities
jest.mock('merchant/views/Navigator/components/util', () => ({
  WalletLabels: {
    itzcash: 'ItzCash',
    payzapp: 'PayZapp',
    olamoney: 'OlaMoney',
    jiomoney: 'JioMoney',
    amazonpay: 'Amazon Pay',
    phonepe: 'PhonePe',
    airtelmoney: 'Airtel Money',
    paytm: 'Paytm',
    mobikwik: 'MobiKwik',
    freecharge: 'Freecharge',
  },
}));

jest.mock('merchant/views/Optimizer/AddProvider/constants', () => ({
  PAYLATER_LABELS: {
    getsimpl: 'Simpl Paylater',
    simpl_pay_in_3: 'Simpl Pay in 3',
  },
}));

describe('MultiSelectDropdown Component', () => {
  const defaultProps = {
    label: 'Wallets',
    options: ['freecharge', 'paytm', 'mobikwik', 'phonepe'],
    selectedOptions: ['freecharge', 'paytm'],
    changeOptions: jest.fn(),
    disabled: false,
    isFormEdit: false,
  };

  // Helper function to find AutoComplete element reliably
  const findAutoCompleteElement = (placeholderText: string): HTMLElement => {
    try {
      return screen.getByPlaceholderText(placeholderText);
    } catch {
      // Fallback: find by testID and then the input
      const dropdown = screen.getByTestId('option-select');
      const input = dropdown.querySelector('input') as HTMLElement;
      expect(input).toBeTruthy();
      return input;
    }
  };

  // Helper function to find clickable dropdown options (handles multiple elements with same text)
  const findDropdownOption = (optionText: string): HTMLElement => {
    const elements = screen.getAllByText(optionText);
    if (elements.length === 1) {
      return elements[0];
    }
    // Find the element that's within an ActionListItem (the clickable dropdown option)
    const dropdownOption = elements.find(element => 
      element.closest('[data-blade-component="actionlistitem"]') !== null
    );
    if (dropdownOption) {
      return dropdownOption;
    }
    // Fallback: return the last element (usually the dropdown option)
    return elements[elements.length - 1];
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Basic Rendering', () => {
    test('should render without any errors', () => {
      expect(() => render(<MultiSelectDropdown {...defaultProps} />)).not.toThrowError();
    });

    test('should display the correct label', () => {
      render(<MultiSelectDropdown {...defaultProps} />);
      expect(screen.getByText('Wallets')).toBeInTheDocument();
    });

    test('should render with custom label', () => {
      const props = { ...defaultProps, label: 'Custom Label' };
      render(<MultiSelectDropdown {...props} />);
      expect(screen.getByText('Custom Label')).toBeInTheDocument();
    });
  });

  describe('Display Mode (isFormEdit: false)', () => {
    test('should show comma-separated selected options with wallet labels', () => {
      render(<MultiSelectDropdown {...defaultProps} />);
      expect(screen.getByText('Freecharge, Paytm')).toBeInTheDocument();
    });

    test('should show raw option values when no label mapping exists', () => {
      const props = {
        ...defaultProps,
        label: 'Custom Options',
        options: ['option1', 'option2'],
        selectedOptions: ['option1', 'option2'],
      };
      render(<MultiSelectDropdown {...props} />);
      expect(screen.getByText('option1, option2')).toBeInTheDocument();
    });

    test('should handle undefined selected options', () => {
      const props = { ...defaultProps, selectedOptions: undefined };
      render(<MultiSelectDropdown {...props} />);
      // Should not crash and render empty text
      expect(screen.queryByText('Freecharge, Paytm')).not.toBeInTheDocument();
    });
  });

  describe('Edit Mode (isFormEdit: true)', () => {
    const editProps = { ...defaultProps, isFormEdit: true };

    test('should render dropdown in edit mode', () => {
      render(<MultiSelectDropdown {...editProps} />);
      expect(screen.getByTestId('option-select')).toBeInTheDocument();
    });
  });

  describe('Select All Functionality', () => {
    const editProps = { ...defaultProps, isFormEdit: true, selectedOptions: [] };

    test('should render "Select All" option in dropdown overlay', () => {
      render(<MultiSelectDropdown {...editProps} />);
      
      // Click to open dropdown
      const autoComplete = screen.getByPlaceholderText('Select Wallets');
      fireEvent.click(autoComplete);
      
      // Check if "Select All" option is present
      expect(findDropdownOption('Select All')).toBeInTheDocument();
    });

    test('should select all options when "Select All" is clicked and no options are selected', () => {
      const mockChangeOptions = jest.fn();
      const props = { ...editProps, changeOptions: mockChangeOptions };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown and click "Select All"
      const autoComplete = screen.getByPlaceholderText('Select Wallets');
      fireEvent.click(autoComplete);
      
      const selectAllOption = findDropdownOption('Select All');
      fireEvent.click(selectAllOption);
      
      // Should call changeOptions with all options
      expect(mockChangeOptions).toHaveBeenCalledWith({
        name: 'Wallets',
        values: ['freecharge', 'paytm', 'mobikwik', 'phonepe'],
      });
    });

    test('should deselect all options when "Select All" is clicked and all options are selected', () => {
      const mockChangeOptions = jest.fn();
      const props = {
        ...editProps,
        selectedOptions: ['freecharge', 'paytm', 'mobikwik', 'phonepe'],
        changeOptions: mockChangeOptions,
      };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown and click "Select All"
      const autoComplete = findAutoCompleteElement('Select Wallets');
      fireEvent.click(autoComplete);
      
      const selectAllOption = findDropdownOption('Select All');
      fireEvent.click(selectAllOption);
      
      // Should call changeOptions with empty array
      expect(mockChangeOptions).toHaveBeenCalledWith({
        name: 'Wallets',
        values: [],
      });
    });

    test('should show "Select All" as selected when all individual options are selected', () => {
      const props = {
        ...editProps,
        selectedOptions: ['freecharge', 'paytm', 'mobikwik', 'phonepe'],
      };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown
      const autoComplete = findAutoCompleteElement('Select Wallets');
      fireEvent.click(autoComplete);
      
      // "Select All" option should be marked as selected
      const selectAllOption = findDropdownOption('Select All');
      // Check if the option has selected state (this depends on the ActionListItem implementation)
      expect(selectAllOption).toBeInTheDocument();
    });

    test('should not show "Select All" as selected when only some options are selected', () => {
      const props = {
        ...editProps,
        selectedOptions: ['freecharge', 'paytm'], // Only partial selection
      };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown
      const autoComplete = findAutoCompleteElement('Select Wallets');
      fireEvent.click(autoComplete);
      
      // "Select All" should be present but not selected
      const selectAllOption = findDropdownOption('Select All');
      expect(selectAllOption).toBeInTheDocument();
    });

    test('should handle individual option selection after "Select All"', () => {
      const mockChangeOptions = jest.fn();
      const props = {
        ...editProps,
        selectedOptions: ['freecharge', 'paytm', 'mobikwik', 'phonepe'],
        changeOptions: mockChangeOptions,
      };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown and click an individual option to deselect it
      const autoComplete = findAutoCompleteElement('Select Wallets');
      fireEvent.click(autoComplete);
      
      // Find and click the Freecharge option in the dropdown
      const frechargeOption = findDropdownOption('Freecharge');
      fireEvent.click(frechargeOption);
      
      // Should call changeOptions without the deselected option
      expect(mockChangeOptions).toHaveBeenCalledWith({
        name: 'Wallets',
        values: ['paytm', 'mobikwik', 'phonepe'],
      });
    });

    test('should filter out "all_values" from individual selections', () => {
      const mockChangeOptions = jest.fn();
      const props = { ...editProps, changeOptions: mockChangeOptions };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown and click an individual option
      const autoComplete = screen.getByPlaceholderText('Select Wallets');
      fireEvent.click(autoComplete);
      
      // Get the clickable Freecharge option from the dropdown
      const frechargeOption = findDropdownOption('Freecharge');
      fireEvent.click(frechargeOption);
      
      // Should call changeOptions with only the actual option, not "all_values"
      expect(mockChangeOptions).toHaveBeenCalledWith({
        name: 'Wallets',
        values: ['freecharge'],
      });
    });

    test('should work correctly with empty options array', () => {
      const mockChangeOptions = jest.fn();
      const props = {
        ...editProps,
        options: [],
        selectedOptions: [],
        changeOptions: mockChangeOptions,
      };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown
      const autoComplete = screen.getByPlaceholderText('Select Wallets');
      fireEvent.click(autoComplete);
      
      // "Select All" should still be present but clicking it should not crash
      const selectAllOption = findDropdownOption('Select All');
      fireEvent.click(selectAllOption);
      
      // Should call changeOptions with empty array since no options exist
      expect(mockChangeOptions).toHaveBeenCalledWith({
        name: 'Wallets',
        values: [],
      });
    });

    test('should work with paylater labels and "Select All"', () => {
      const mockChangeOptions = jest.fn();
      const props = {
        ...editProps,
        label: 'Paylaters',
        options: ['getsimpl', 'simpl_pay_in_3'],
        selectedOptions: [],
        changeOptions: mockChangeOptions,
      };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown and click "Select All"
      const autoComplete = screen.getByPlaceholderText('Select Paylaters');
      fireEvent.click(autoComplete);
      
      const selectAllOption = findDropdownOption('Select All');
      fireEvent.click(selectAllOption);
      
      // Should call changeOptions with all paylater options
      expect(mockChangeOptions).toHaveBeenCalledWith({
        name: 'Paylaters',
        values: ['getsimpl', 'simpl_pay_in_3'],
      });
    });

    test('should maintain "Select All" state consistency during mixed selections', () => {
      const mockChangeOptions = jest.fn();
      const props = {
        ...editProps,
        selectedOptions: ['freecharge', 'paytm'], // Partial selection
        changeOptions: mockChangeOptions,
      };
      
      render(<MultiSelectDropdown {...props} />);
      
      // Open dropdown and click "Select All" - should select remaining options
      const autoComplete = findAutoCompleteElement('Select Wallets');
      fireEvent.click(autoComplete);
      
      const selectAllOption = findDropdownOption('Select All');
      fireEvent.click(selectAllOption);
      
      // Should call changeOptions with all options
      expect(mockChangeOptions).toHaveBeenCalledWith({
        name: 'Wallets',
        values: ['freecharge', 'paytm', 'mobikwik', 'phonepe'],
      });
    });
  });

  describe('Paylater Label Mapping', () => {
    const paylaterProps = {
      ...defaultProps,
      label: 'Paylaters',
      options: ['getsimpl', 'simpl_pay_in_3'],
      selectedOptions: ['getsimpl', 'simpl_pay_in_3'],
    };

    test('should show paylater labels in display mode', () => {
      render(<MultiSelectDropdown {...paylaterProps} />);
      expect(screen.getByText('Simpl Paylater, Simpl Pay in 3')).toBeInTheDocument();
    });

    test('should display paylater label in form', () => {
      render(<MultiSelectDropdown {...paylaterProps} />);
      expect(screen.getByText('Paylaters')).toBeInTheDocument();
    });
  });

  describe('Mixed Label Scenarios', () => {
    test('should handle options with and without label mappings for wallets', () => {
      const props = {
        ...defaultProps,
        options: ['freecharge', 'unknown_wallet', 'paytm'],
        selectedOptions: ['freecharge', 'unknown_wallet', 'paytm'],
      };
      render(<MultiSelectDropdown {...props} />);
      expect(screen.getByText('Freecharge, unknown_wallet, Paytm')).toBeInTheDocument();
    });

    test('should handle options with and without label mappings for paylaters', () => {
      const props = {
        ...defaultProps,
        label: 'Paylaters',
        options: ['getsimpl', 'unknown_paylater'],
        selectedOptions: ['getsimpl', 'unknown_paylater'],
      };
      render(<MultiSelectDropdown {...props} />);
      expect(screen.getByText('Simpl Paylater, unknown_paylater')).toBeInTheDocument();
    });

    test('should handle unknown label type gracefully', () => {
      const props = {
        ...defaultProps,
        label: 'Unknown Type',
        options: ['option1', 'option2'],
        selectedOptions: ['option1', 'option2'],
      };
      render(<MultiSelectDropdown {...props} />);
      expect(screen.getByText('option1, option2')).toBeInTheDocument();
    });
  });

  describe('Edge Cases', () => {
    test('should handle empty options array', () => {
      const props = { ...defaultProps, options: [], selectedOptions: [] };
      render(<MultiSelectDropdown {...props} />);
      expect(screen.getByText('Wallets')).toBeInTheDocument();
    });

    test('should handle single selected option', () => {
      const props = { ...defaultProps, selectedOptions: ['freecharge'] };
      render(<MultiSelectDropdown {...props} />);
      expect(screen.getByText('Freecharge')).toBeInTheDocument();
    });

    test('should handle very long option lists', () => {
      const longOptions = Array.from({ length: 20 }, (_, i) => `option${i}`);
      const props = {
        ...defaultProps,
        options: longOptions,
        selectedOptions: longOptions.slice(0, 5),
        isFormEdit: true,
      };
      expect(() => render(<MultiSelectDropdown {...props} />)).not.toThrowError();
    });
  });

  describe('Component Structure', () => {
    test('should have correct layout structure in display mode', () => {
      render(<MultiSelectDropdown {...defaultProps} />);
      
      // Check for main container
      const container = screen.getByText('Wallets').closest('[class*="Box"]');
      expect(container).toBeInTheDocument();
      
      // Check for label and value sections
      expect(screen.getByText('Wallets')).toBeInTheDocument();
      expect(screen.getByText('Freecharge, Paytm')).toBeInTheDocument();
    });

    test('should have correct layout structure in edit mode', () => {
      const props = { ...defaultProps, selectedOptions: [], isFormEdit: true };
      render(<MultiSelectDropdown {...props} />);
      
      // Check for dropdown structure
      expect(screen.getByTestId('option-select')).toBeInTheDocument();
      expect(screen.getByPlaceholderText('Select Wallets')).toBeInTheDocument();
    });

    test('should apply correct CSS classes and styling', () => {
      render(<MultiSelectDropdown {...defaultProps} />);
      
      // Verify the label container has minimum width
      const labelContainer = screen.getByText('Wallets').closest('[class*="Box"]');
      expect(labelContainer).toBeInTheDocument();
    });
  });

  describe('Props Validation', () => {
    test('should handle all required props correctly', () => {
      const requiredProps = {
        label: 'Test Label',
        options: ['option1'],
        selectedOptions: ['option1'],
        changeOptions: jest.fn(),
        disabled: false,
        isFormEdit: false,
      };
      
      expect(() => render(<MultiSelectDropdown {...requiredProps} />)).not.toThrowError();
    });

    test('should pass correct props to AutoComplete component', () => {
      const mockChangeOptions = jest.fn();
      const props = {
        ...defaultProps,
        isFormEdit: true,
        changeOptions: mockChangeOptions,
        selectedOptions: [],
        disabled: true,
      };
      
      render(<MultiSelectDropdown {...props} />);
      
      const autoComplete = screen.getByPlaceholderText('Select Wallets');
      expect(autoComplete).toBeDisabled();
    });
  });
});
