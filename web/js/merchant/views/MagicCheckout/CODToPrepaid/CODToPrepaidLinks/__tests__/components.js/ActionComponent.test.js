import { render, screen, userEvent } from 'test-utils';
import ActionComponent from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/ActionComponent';

describe('testing action component', () => {
  test('component should render properly', () => {
    render(<ActionComponent disableActions />);
    expect(screen.getByTestId('expire-btn')).toBeInTheDocument();
  });

  test('component should call onReview function', async () => {
    const mockedOnReviewFn = jest.fn();
    render(<ActionComponent item={{ id: 'order_123' }} onReview={mockedOnReviewFn} />);
    const expireCta = screen.getByTestId('expire-btn');
    await userEvent.click(expireCta);
  });
});
