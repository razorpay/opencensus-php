import { analyticsTrack } from '@dashboard/shared-utils/analytics';
import { renderApp, useMobileMock } from './fixtures/mocks/Details';
import { screen, userEvent } from 'apps/self-serve/src/services/test/test-utils';

describe('Details', () => {
  test('should render link text correctly on desktop', () => {
    useMobileMock.useMobile.mockReturnValue(false);
    renderApp();
    const linkElement = screen.getByText('Details');
    expect(linkElement).toBeInTheDocument();
  });

  test('should not render link text on mobile', () => {
    useMobileMock.useMobile.mockReturnValueOnce(true);
    renderApp();
    const linkElement = screen.queryByText('Details');
    expect(linkElement).toBeNull();
  });

  test('should call history.push with correct URL and trackAction on click', async () => {
    window.location.hash = '#test';
    const { history } = renderApp();
    const linkElement = screen.getByText('Details');
    await userEvent.click(linkElement);
    expect(history.location.pathname).toBe('/base/123');
    expect(history.location.search).toBe('?init_page=page');
    expect(history.location.hash).toBe('#test');
    expect(analyticsTrack).toHaveBeenCalledWith({
      actionName: 'Clicked',
      objectName: 'Transaction Details Button',
      screen: 'Transactions',
      properties: {
        page: 'Transactions',
        paymentMethodSelected: 'All',
        section: 'page',
        transactionIDActual: '123',
        version: 'v2',
      },
    });
  });
});
