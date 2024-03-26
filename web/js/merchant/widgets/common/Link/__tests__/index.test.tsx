import React from 'react';

import { LinkWidget } from 'merchant/widgets/common/Link';
import { render, screen, userEvent } from 'test-utils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const commonProps = { title: 'View', icon: 'arrow_right', icon_position: 'right' as const };

describe('Widgets->Common->Link', () => {
  test('should render component', () => {
    render(<LinkWidget {...commonProps} action="http://www.razorpay.com" />);
    expect(screen.getByRole('link', { name: commonProps.title })).toBeVisible();
  });

  test('should not display link CTA if action is missing', () => {
    render(<LinkWidget {...commonProps} />);
    expect(screen.queryByRole('link', { name: commonProps.title })).not.toBeInTheDocument();
  });

  test('should navigate to internal dashboard url', async () => {
    const { history } = render(<LinkWidget {...commonProps} action="additional_website" />);
    await userEvent.click(screen.getByRole('link', { name: commonProps.title }));
    expect(history.location.pathname).toBe(ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS);
  });
});
