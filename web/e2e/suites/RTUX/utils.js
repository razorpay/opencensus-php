import { expect } from 'utils/base';

const UCS_DATA_API_URL = '**/ucs/**/GetComponentData';

export async function getRTUXResponse({ page }) {
  let result;
  try {
    const [response] = await Promise.all([page.waitForResponse(UCS_DATA_API_URL)]);
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
  for (const widget of components) {
    if (widget.type === widgetKey) {
      return widget;
    } else if (widget.type === 'layout') {
      const result = getWidgetResponse(widget.components, widgetKey);
      if (result) {
        return result;
      }
    }
  }
  return undefined;
}

export async function assertAPICallForDataRefresh({ page, title }) {
  const container = await page.getByTestId(`widget-${title}`);
  await expect(container).toBeVisible();
  const dropDown = await container.getByTestId('date-picker-component');
  await expect(dropDown).toBeVisible();

  await dropDown.click();

  const last30daysOption = page.getByRole('option', { name: 'Last month' });
  await Promise.all([page.waitForResponse(UCS_DATA_API_URL), last30daysOption.click()]);
}
