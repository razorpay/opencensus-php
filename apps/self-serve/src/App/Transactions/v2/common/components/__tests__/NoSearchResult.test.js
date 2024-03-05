import { renderApp } from './fixtures/mocks/NoSearchResult';
import { screen } from 'apps/self-serve/src/services/test/test-utils';

describe('NoSearchResult', () => {
  test('should render NoSearchResultTemplate correctly with payments config', () => {
    renderApp({ page: 'payments' });
    expect(screen.getByText('No payment in selected duration')).toBeInTheDocument();
    expect(
      screen.getByText('Search using different keywords or time duration'),
    ).toBeInTheDocument();
    expect(screen.getByAltText('no payments')).toBeInTheDocument();
  });

  test('should render NoSearchResultTemplate correctly with failed payments config', () => {
    renderApp({ page: 'failed payments' });
    expect(screen.getByText('No failed payment in selected duration')).toBeInTheDocument();
    expect(
      screen.getByText('Search using different keywords or time duration'),
    ).toBeInTheDocument();
    expect(screen.getByAltText('no failed payments')).toBeInTheDocument();
  });

  test('should render NoSearchResultTemplate correctly with refunds config', () => {
    renderApp({ page: 'refunds' });
    expect(screen.getByText('No refund in selected duration')).toBeInTheDocument();
    expect(
      screen.getByText('Search using different keywords or time duration'),
    ).toBeInTheDocument();
    expect(screen.getByAltText('no refunds')).toBeInTheDocument();
  });
});
