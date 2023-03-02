import { App } from 'merchant/views/PaymentHandle/__test__/mocks/fixtures/ListFilter';
import { render, screen } from 'test-utils';

describe('Payment Handle List Filter', () => {
  const renderApp = (props = {}) => {
    return render(<App {...props} />);
  };

  it('List filter component should be defined', () => {
    expect(App).toBeDefined();
  });

  it('List filter component should have Test Information', () => {
    renderApp();
    const element = screen.getByTestId('ds-test-mode');
    expect(element).toHaveTextContent(
      'You are in Test Mode, so only test data is shown. Switch to Live mode to see real transactions data.',
    );
  });
});
