import { renderApp } from './mocks/fixtures/EntityAnalytics';
import { screen, checkIfComponentIsEmpty } from 'apps/self-serve/src/services/test/test-utils';

describe('EntityAnalytics', () => {
  test('should render refunds overview', () => {
    renderApp({
      type: 'Refunds',
    });
    expect(screen.getByText('Refunds overview')).toBeInTheDocument();
  });

  test('should render failed payments overview', () => {
    renderApp({
      type: 'Failed',
    });
    expect(screen.getByText('Failed payments overview')).toBeInTheDocument();
  });

  test('should not render anything when type is not correct', () => {
    renderApp({
      type: 'xyz',
    });
    checkIfComponentIsEmpty();
  });
});
