import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import Feedback from '@apps/digital-bills/src/views/Feedback';

describe('Feedback', () => {
  test('should render Feedback component', async () => {
    const { getByText, getByRole } = renderWithWrappers(<Feedback />);
    expect(getByText('Feedback and Complaints')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    let iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/feedback/campaigns');

    const feedbackOptions = getByRole('combobox', { name: 'Select Listing' });
    await userEvent.click(feedbackOptions);
    const responsesOption = getByRole('option', { name: 'Responses' });
    await userEvent.click(responsesOption);
    iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/feedback/responses');
  });
});
