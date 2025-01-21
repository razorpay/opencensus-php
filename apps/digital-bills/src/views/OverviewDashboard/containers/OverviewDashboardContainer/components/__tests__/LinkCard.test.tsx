import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import LinkCard from '@apps/digital-bills/src/views/OverviewDashboard/containers/OverviewDashboardContainer/components/LinkCard';

describe('LinkCard', () => {
  test('should render LinkCard component', async () => {
    const { getByText } = renderWithWrappers(<LinkCard label="Test Label" href="/testPath" />);

    const linkLabel = getByText('Test Label');
    expect(linkLabel).toBeInTheDocument();
    await userEvent.click(linkLabel);
    expect(window.location.pathname).toBe('/testPath');
  });
});
