import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import SurveyManagement from '@apps/digital-bills/src/views/SurveyManagement';

describe('SurveyManagement', () => {
  test('should render SurveyManagement component', async () => {
    const { getByText } = renderWithWrappers(<SurveyManagement />);
    expect(getByText('Survey Management')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    const iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/auto-engage/surveys');
  });
});
