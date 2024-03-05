import {
  renderApp,
  useMobileMock,
} from 'self-serve/src/App/Transactions/v2/common/components/__tests__/fixtures/mocks/CreatedOn';
import { screen } from 'apps/self-serve/src/services/test/test-utils';

describe('CreatedOn', () => {
  const created_at = 1630000000;
  test('should render full created date on desktop', () => {
    useMobileMock.useMobile.mockReturnValue(false);
    renderApp({ created_at });
    const createdAtElement = screen.getByText('Aug 26, 2021, 5:46pm');
    expect(createdAtElement).toBeInTheDocument();
  });

  test('should render split created date on mobile', () => {
    useMobileMock.useMobile.mockReturnValue(true);
    renderApp({ created_at });
    const dateElement = screen.getByText('Aug 26, 2021');
    const timeElement = screen.getByText('5:46pm');
    expect(dateElement).toBeInTheDocument();
    expect(timeElement).toBeInTheDocument();
  });
});
