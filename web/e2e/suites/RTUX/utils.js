import { routes } from 'testConstants';
import { expect } from '@playwright/test';

const UCS_DATA_API_URL = '**/ucs/**/GetComponentData';

export async function getRTUXResponse({ page }) {
  let result;
  try {
    const [response] = await Promise.all([
      page.waitForResponse(UCS_DATA_API_URL),
      page.goto(routes.DASHBOARD),
    ]);
    const res = await response.json();
    result = res.components;
    if (!result.length) {
      throw new Error('Invalid UCS Data response');
    }
    return result;
  } catch (err) {
    throw new Error('Failure in UCS API');
  }
}

export function getWidgetResponse(components, widgetKey) {
  return components.find((c) => c.type === widgetKey);
}

export async function assertAPICallForDataRefresh({ page, title }) {
  const container = await page.getByText(`${title}Last weekTodayLast weekLast 30 days`);
  await expect(container).toBeVisible();
  const dropDown = await container.getByTestId('date-picker-component');
  await expect(dropDown).toBeVisible();

  await dropDown.click();

  const last30daysOption = page.getByRole('option', { name: 'Last 30 days' });
  await Promise.all([page.waitForResponse(UCS_DATA_API_URL), last30daysOption.click()]);
}
