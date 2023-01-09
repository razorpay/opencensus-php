import { EmptyComponent } from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/components/Content';
import { screen } from 'test-utils';
import { render } from '@testing-library/react';

const initProps = {
  sampleUrl: 'https://test-download-url.com',
};

const App = ({ ...props }) => {
  return <EmptyComponent {...props} {...initProps} />;
};

describe('Empty Component', () => {
  test('should render empty component when file uploaded yet', () => {
    render(<App />);
    expect(screen.queryByTestId('order-history-empty-component')).toBeInTheDocument();
  });

  test('should have href for sample url', () => {
    const { container } = render(<App />);
    expect(container.querySelector('a').getAttribute('href')).toBe('https://test-download-url.com');
  });
});
