import { renderApp, assertRedirect } from './mocks/fixtures/SuccessRateBanner';
import { screen, userEvent } from 'apps/self-serve/src/services/test/test-utils';

describe('SuccessRateBanner', () => {
  test('should render the banner with the correct text', () => {
    renderApp({
      successRateData: 99,
    });
    expect(screen.getByText('99% success rate')).toBeInTheDocument();
    expect(screen.getByAltText('success rate emoji')).toBeInTheDocument();
    expect(
      screen.getByRole('button', {
        name: 'View dashboard',
      }),
    ).toBeInTheDocument();
  });

  test('should not show emoji when success rate is less than 75', () => {
    renderApp({
      successRateData: 55,
    });
    expect(screen.queryByAltText('success rate emoji')).not.toBeInTheDocument();
  });

  test('should redirect to success rate page when clicked on link', async () => {
    renderApp();
    const gotoSuccessRateButton = screen.getByRole('button', {
      name: 'View dashboard',
    });
    await userEvent.click(gotoSuccessRateButton);
    await assertRedirect();
  });
});
