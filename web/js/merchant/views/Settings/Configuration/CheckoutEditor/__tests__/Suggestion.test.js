import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import Suggestion from 'merchant/views/Settings/Configuration/CheckoutEditor/Suggestion.tsx';
import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context';

// Mock the context
jest.mock('../context', () => ({
  useCheckoutEditor: jest.fn(),
}));

describe('Suggestion Component', () => {
  it('should call handleSuggestionSubmit with correct arguments on valid submission', () => {
    const mockHandleSuggestionSubmit = jest.fn();

    // Mock the context to return the function
    useCheckoutEditor.mockReturnValue({
      handleSuggestionSubmit: mockHandleSuggestionSubmit,
    });

    render(<Suggestion />);

    // Simulate typing a suggestion
    const input = screen.getByPlaceholderText('Add suggestion');
    fireEvent.change(input, { target: { value: 'New Feature Suggestion' } });

    // Simulate clicking the submit button
    const submitButton = screen.getByRole('button', { name: /submit/i });
    fireEvent.click(submitButton);

    // Assertions: Check that the mock function is called with correct data
    expect(mockHandleSuggestionSubmit).toHaveBeenCalledTimes(1);
    expect(mockHandleSuggestionSubmit).toHaveBeenCalledWith({
      suggestion: 'New Feature Suggestion',
      page: 'checkout-features',
    });
  });

  it('should disable the submit button when input is empty', () => {
    render(<Suggestion />);

    // Ensure the submit button is disabled initially
    const submitButton = screen.getByRole('button', { name: /submit/i });
    expect(submitButton).toHaveAttribute('aria-disabled', 'true');

    // Simulate typing in the input to enable the button
    const input = screen.getByPlaceholderText('Add suggestion');
    fireEvent.change(input, { target: { value: 'A Suggestion' } });
    expect(submitButton).toHaveAttribute('aria-disabled', 'false');
  });
});
