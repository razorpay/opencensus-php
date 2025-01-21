import React from 'react';
import { Text } from '@razorpay/blade/components';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { waitFor, userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import InfoContainer from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/InfoContainer';

describe('InfoContainer', () => {
  test('should render InfoContainer component', async () => {
    const { getByText, getByTestId, getByRole } = renderWithWrappers(
      <InfoContainer
        title={<Text>Test Title</Text>}
        info="Test Info"
        height="50px"
        children={<Text>Test Children</Text>}
      />,
    );

    // 'title' prop
    const title = getByText('Test Title');
    expect(title).toBeInTheDocument();

    // 'info' prop
    const tooltipIcon = getByTestId('tooltip-interactive-wrapper');
    expect(tooltipIcon).toBeInTheDocument();
    await userEvent.hover(tooltipIcon);
    await waitFor(() => {
      expect(getByText('Test Info')).toBeInTheDocument();
    });

    // 'children' prop
    expect(getByText('Test Children')).toBeInTheDocument();

    // 'height' prop
    const cardElement = title.closest('div[data-blade-component=card]');
    expect(cardElement).toHaveStyle('height: 50px');

    // Separator
    expect(getByRole('separator')).toBeInTheDocument();
  });

  test("should render InfoContainer component without info and with default card height of '100%', when not passed in props", () => {
    const { getByText, queryByTestId } = renderWithWrappers(
      <InfoContainer title={<Text>Test Title</Text>} />,
    );

    // 'title' prop
    const title = getByText('Test Title');
    expect(title).toBeInTheDocument();

    // 'info' prop
    const tooltipIcon = queryByTestId('tooltip-interactive-wrapper');
    expect(tooltipIcon).not.toBeInTheDocument();

    // 'height' prop
    const cardElement = title.closest('div[data-blade-component=card]');
    expect(cardElement).toHaveStyle('height: 100%');
  });
});
