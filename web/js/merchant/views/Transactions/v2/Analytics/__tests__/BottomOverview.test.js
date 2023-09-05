import { screen, userEvent } from 'test-utils';
import { assertRedirect, renderApp, tiles } from './mocks/fixtures/BottomOverview';

describe('BottomAnalyticsOverview', () => {
  test('should render all payment types', () => {
    renderApp();
    expect(screen.getByText('Refunds')).toBeInTheDocument();
    expect(screen.getByText('Disputes')).toBeInTheDocument();
    expect(screen.getByText('Failed')).toBeInTheDocument();
  });

  test('should show loading shimmer when data is loading', () => {
    renderApp({
      loading: true,
    });
    expect(screen.getAllByTestId('loading-shimmer')).toHaveLength(tiles.length);
  });

  test(`should show refresh again caption when data load failed`, () => {
    renderApp({
      failed: true,
    });
    const failedFetch = screen.getAllByText("Couldn't be loaded");
    expect(failedFetch).toHaveLength(3);
  });

  tiles.forEach((name) => {
    test(`should redirect on ${name} view details click`, async () => {
      renderApp();
      const viewDetailsBtn = screen.getByLabelText(`view-${name}-details`);
      expect(viewDetailsBtn).toBeInTheDocument();
      await userEvent.click(viewDetailsBtn);
      await assertRedirect(name);
    });
  });
});
