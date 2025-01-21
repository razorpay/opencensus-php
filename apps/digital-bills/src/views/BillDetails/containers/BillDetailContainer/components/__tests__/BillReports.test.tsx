import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import BillReports from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/BillReports';

describe('BillReports', () => {
  test('should render the BillReports component', async () => {
    const { getAllByRole, getByText } = renderWithWrappers(
      <BillReports
        visits={[]}
        deliveryReport={{
          sms: [],
          email: [],
          whatsapp: [],
        }}
        timestamp="2007-12-03T10:15:30Z"
      />,
    );
    const tabs = getAllByRole('tab');
    expect(tabs).toHaveLength(2);
    expect(tabs[0]).toHaveTextContent('Channel Status Report');
    expect(getByText('WhatsApp')).toBeInTheDocument();

    expect(tabs[1]).toHaveTextContent('Bill Read Receipt');
    await userEvent.click(tabs[1]);
    expect(getByText('Operating System')).toBeInTheDocument();
  });
});
