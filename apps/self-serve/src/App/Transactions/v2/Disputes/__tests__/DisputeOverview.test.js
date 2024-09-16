import 'jest-location-mock';
import { renderApp } from 'apps/self-serve/src/App/Transactions/v2/Disputes/__tests__/mocks/fixtures/DisputeOverview';
import { mockServerResponse } from 'apps/self-serve/src/App/Transactions/v2/Disputes/__tests__/mocks/handlers';
import { paymentDurationOptionsMap } from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import { screen, userEvent } from 'apps/self-serve/src/services/test/test-utils';

jest.setTimeout(35000);

describe('DisputeOverview', () => {
  test('should render component correctly with correct props', () => {
    renderApp();
    expect(screen.getByText('Disputes raised')).toBeInTheDocument();
  });

  test('should render default Last 7 days and duration options', async () => {
    renderApp();
    const dropdownTrigger = screen.getByRole('button', { name: 'Today' });
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);
    Object.values(paymentDurationOptionsMap).forEach((duration) => {
      const dropdownOption = screen.getByRole('menuitem', { name: duration });
      expect(dropdownOption).toBeInTheDocument();
    });
  });

  test('should allow to change duration options', async () => {
    renderApp();
    let dropdownTrigger = screen.getByRole('button', { name: 'Today' });
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);
    await userEvent.click(screen.getByRole('menuitem', { name: 'Last 30 days' }));
    dropdownTrigger = screen.getByRole('button', { name: 'Last 30 days' });
    expect(dropdownTrigger).toBeInTheDocument();
    await userEvent.click(dropdownTrigger);
    await userEvent.click(screen.getAllByRole('menuitem', { name: 'Last 7 days' })[0]);
    dropdownTrigger = screen.getByRole('button', { name: 'Last 7 days' });
    expect(dropdownTrigger).toBeInTheDocument();
  });

  test('should not show dispute count', () => {
    renderApp();
    mockServerResponse({ kind: 'success', data: { totalDisputeAmount: 0 } });
    expect(screen.getByText(/no disputes created/)).toBeInTheDocument();
  });
});
