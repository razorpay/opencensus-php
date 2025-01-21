import React from 'react';

import ChannelStatusTable from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/ChannelStatusTable/ChannelStatusTable';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { DELIVERY_REPORT_MOCK } from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/__tests__/mocks';

describe('ChannelStatusTable', () => {
  test('should render the ChannelStatusTable component', () => {
    const { getByText, getAllByText } = renderWithWrappers(
      <ChannelStatusTable
        deliveryReports={DELIVERY_REPORT_MOCK}
        timestamp="2007-12-03T10:15:30Z"
      />,
    );
    expect(getByText('Title')).toBeInTheDocument();
    expect(getByText('SMS')).toBeInTheDocument();
    expect(getByText('E-mail')).toBeInTheDocument();
    expect(getByText('WhatsApp')).toBeInTheDocument();

    expect(getByText('Triggered')).toBeInTheDocument();
    expect(getByText('Invoice Date & Time')).toBeInTheDocument();
    expect(getByText('Upload Date & Time')).toBeInTheDocument();
    expect(getByText('API Call Date & Time')).toBeInTheDocument();
    expect(getByText('Delivery')).toBeInTheDocument();

    expect(getAllByText('Yes')).toHaveLength(3);
  });
});
