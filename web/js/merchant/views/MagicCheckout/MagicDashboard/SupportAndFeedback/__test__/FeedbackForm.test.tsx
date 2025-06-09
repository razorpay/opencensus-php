import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';
import { createStore } from 'redux';
import FeedbackForm from 'merchant/views/MagicCheckout/MagicDashboard/SupportAndFeedback/components/FeedbackForm';
import { submitFeedbackAndRating } from 'merchant/views/MagicCheckout/MagicDashboard/SupportAndFeedback/components/api';
import { showNotification } from 'merchant_common/reducers/notifications';

jest.mock('merchant/views/MagicCheckout/MagicDashboard/SupportAndFeedback/components/api');
jest.mock('merchant_common/reducers/notifications');

const initialState = {
  session: {
    user: {
      merchant: {
        id: 'test-merchant-id',
        name: 'Test Merchant',
      },
    },
  },
};

const mockReducer = (state = initialState) => state;
const store = createStore(mockReducer);

describe('FeedbackForm Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  const renderWithProviders = (component) => {
    return render(
      <Provider store={store}>
        <BladeProvider themeTokens={bladeTheme}>{component}</BladeProvider>
      </Provider>,
    );
  };

  it('should render the feedback form with correct title and rating stars', () => {
    renderWithProviders(<FeedbackForm />);

    expect(screen.getByText('Share your feedback')).toBeInTheDocument();
    expect(
      screen.getByText('How would you rate your experience with Razorpay?'),
    ).toBeInTheDocument();
    const stars = screen.getAllByText('★');
    expect(stars).toHaveLength(5);
  });

  it('should display poor experience message and feedback form for ratings <= 4', () => {
    renderWithProviders(<FeedbackForm />);

    const stars = screen.getAllByText('★');
    expect(screen.queryByText(/apologize/i)).not.toBeInTheDocument();
    fireEvent.click(stars[2]);
    expect(screen.getByText(/apologize/i)).toBeInTheDocument();
    expect(
      screen.getByRole('textbox', {
        name: /Please give us detailed feedback on how we can improve/i,
      }),
    ).toBeInTheDocument();
  });

  it('should display excellent message and Shopify app store link for ratings > 4', () => {
    renderWithProviders(<FeedbackForm />);

    const stars = screen.getAllByText('★');
    fireEvent.click(stars[4]);
    expect(
      screen.getByText((content, element) => {
        const hasText = (text) => content.includes(text);
        return hasText('Excellent') || hasText('glad');
      }),
    ).toBeInTheDocument();
    expect(screen.getByText(/Shopify app store/i)).toBeInTheDocument();
    expect(screen.getByRole('link')).toHaveAttribute(
      'href',
      'https://apps.shopify.com/razorpay-checkout#adp-reviews',
    );
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
  });

  it('should handle star hover interactions correctly', () => {
    renderWithProviders(<FeedbackForm />);

    const stars = screen.getAllByText('★');
    fireEvent.mouseEnter(stars[2]);
    expect(screen.queryByText(/apologize/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/excellent|glad/i)).not.toBeInTheDocument();
    fireEvent.mouseLeave(stars[2]);
  });

  it('should allow feedback submission with necessary UI elements', () => {
    renderWithProviders(<FeedbackForm />);

    const stars = screen.getAllByText('★');
    fireEvent.click(stars[1]);
    const textareaElement = screen.getByRole('textbox');
    fireEvent.change(textareaElement, { target: { value: 'Needs improvement' } });
    const submitButton = screen.getByRole('button', { name: /submit feedback/i });
    expect(submitButton).toBeInTheDocument();
  });

  it('should reset the form when Cancel is clicked', () => {
    renderWithProviders(<FeedbackForm />);

    const stars = screen.getAllByText('★');
    fireEvent.click(stars[2]);
    const textareaElement = screen.getByRole('textbox');
    fireEvent.change(textareaElement, { target: { value: 'My feedback' } });
    fireEvent.click(screen.getByRole('button', { name: /cancel/i }));
    expect(screen.queryByText(/apologize/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
  });

  it('should submit feedback successfully and show success notification', async () => {
    (submitFeedbackAndRating as jest.Mock).mockResolvedValueOnce({});
    renderWithProviders(<FeedbackForm />);

    const stars = screen.getAllByText('★');
    fireEvent.click(stars[2]);
    const textareaElement = screen.getByRole('textbox');
    fireEvent.change(textareaElement, { target: { value: 'Test feedback' } });

    const submitButton = screen.getByRole('button', { name: /submit feedback/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(submitFeedbackAndRating).toHaveBeenCalledWith({
        rating: 3,
        feedback: 'Test feedback',
        merchant_id: 'test-merchant-id',
        merchant_name: 'Test Merchant',
      });
    });

    expect(showNotification).toHaveBeenCalledWith({
      type: 'success',
      message: 'Feedback submitted successfully',
    });
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
  });

  it('should show error notification when feedback submission fails', async () => {
    (submitFeedbackAndRating as jest.Mock).mockRejectedValueOnce(new Error('API Error'));
    renderWithProviders(<FeedbackForm />);

    const stars = screen.getAllByText('★');
    fireEvent.click(stars[1]);
    const textareaElement = screen.getByRole('textbox');
    fireEvent.change(textareaElement, { target: { value: 'Error test feedback' } });

    const submitButton = screen.getByRole('button', { name: /submit feedback/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(submitFeedbackAndRating).toHaveBeenCalledWith({
        rating: 2,
        feedback: 'Error test feedback',
        merchant_id: 'test-merchant-id',
        merchant_name: 'Test Merchant',
      });
    });

    expect(showNotification).toHaveBeenCalledWith({
      type: 'error',
      message: 'Something went wrong',
    });

    expect(screen.getByRole('textbox')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toHaveValue('Error test feedback');
  });

  it('should submit feedback without comments for high ratings', async () => {
    (submitFeedbackAndRating as jest.Mock).mockResolvedValueOnce({});
    renderWithProviders(<FeedbackForm />);

    const stars = screen.getAllByText('★');
    fireEvent.click(stars[4]);

    const submitButton = screen.getByRole('button', { name: /submit feedback/i });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(submitFeedbackAndRating).toHaveBeenCalledWith({
        rating: 5,
        feedback: '',
        merchant_id: 'test-merchant-id',
        merchant_name: 'Test Merchant',
      });
    });

    expect(showNotification).toHaveBeenCalledWith({
      type: 'success',
      message: 'Feedback submitted successfully',
    });
  });
});
