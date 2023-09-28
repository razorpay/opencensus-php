import MagicIntelligence from 'merchant/views/MagicCheckout/MagicIntelligence';
import { fireEvent, screen, render } from 'test-utils';

describe('Magic Intelligence component', () => {
  test('should show routes in the container', () => {
    render(<MagicIntelligence />);
    expect(screen.queryByTestId('magic-intelligence-tabbed-container')).toBeInTheDocument();
  });

  test('should render different component on another tab click', () => {
    render(<MagicIntelligence />);
    const blocklist = screen.getByText(/Blocklist/i);
    expect(blocklist).toBeInTheDocument();
    fireEvent.click(blocklist);
  });
});
