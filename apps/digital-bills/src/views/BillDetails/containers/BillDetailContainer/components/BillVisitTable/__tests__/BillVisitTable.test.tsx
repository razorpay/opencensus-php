import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import BillVisitTable from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/BillVisitTable';

describe('BillVisitTable', () => {
  test('should render the BillVisitTable component', () => {
    const { getByText } = renderWithWrappers(
      <BillVisitTable
        visits={[
          {
            userAgent:
              'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
            ip: '1234',
            source: 'source',
            visitedAt: 'visitedAt',
          },
        ]}
      />,
    );

    expect(getByText('Device Name')).toBeInTheDocument();
    expect(getByText('Browser')).toBeInTheDocument();
    expect(getByText('Operating System')).toBeInTheDocument();
    expect(getByText('IP Address')).toBeInTheDocument();

    expect(getByText('Windows')).toBeInTheDocument();
    expect(getByText('Chrome')).toBeInTheDocument();
    expect(getByText('1234')).toBeInTheDocument();
  });

  test('should render the BillVisitTable component with placeholder content when no records available', () => {
    const { getByText } = renderWithWrappers(<BillVisitTable visits={[]} />);
    expect(getByText('No data found')).toBeInTheDocument();
  });
});
