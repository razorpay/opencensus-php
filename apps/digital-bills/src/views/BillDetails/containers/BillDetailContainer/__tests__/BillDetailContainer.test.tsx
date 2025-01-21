import React from 'react';
import { useQuery } from '@tanstack/react-query';

import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import BillDetailContainer from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/BillDetailContainer';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { DELIVERY_REPORT_MOCK } from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/__tests__/mocks';

const testData = {
  contactNo: '+91-1234 5678 21',
  email: 'email@email.com',
  billId: 'bill_kmkejlsdcg8hr',
  amount: 221821,
  transactionType: 'DIGITAL',
  invoiceNo: '#AD12ADS',
  timestamp: '2007-12-03T10:15:30Z',
  billDocumentUrl: 'www.example.com/pdf',
  brandLogo: 'www.example.com/logo',
  brandName: 'Brand Name',
  storeAddress: 'Store Address',
  visits: [],
  deliveryReport: DELIVERY_REPORT_MOCK,
  legacyEntityId: '1234',
};

jest.mock('@tanstack/react-query', () => {
  const actualReactQuery = jest.requireActual('@tanstack/react-query');
  return {
    ...actualReactQuery,
    useQuery: jest.fn(),
  };
});

describe('BillDetailContainer', () => {
  beforeEach(() => {
    (useQuery as jest.Mock).mockReturnValue({ isLoading: false, isError: false, data: {} });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render the BillDetailContainer component', async () => {
    const { getByText, getAllByRole } = renderWithWrappers(<BillDetailContainer {...testData} />);
    expect(getByText('Bill Details')).toBeInTheDocument();

    // Breadcrumbs
    expect(getByText('BillMe')).toBeInTheDocument();
    expect(getByText('Bills')).toBeInTheDocument();
    expect(getByText('Bill ID - bill_kmkejlsdcg8hr')).toBeInTheDocument;

    // Brand Details
    expect(getByText('Brand Name')).toBeInTheDocument();
    expect(getByText('Store Address')).toBeInTheDocument;

    // Bill Info
    expect(getByText('Type of Transaction:')).toBeInTheDocument();
    expect(getByText('Digital')).toBeInTheDocument();

    // Table
    const tabs = getAllByRole('tab');
    expect(tabs).toHaveLength(2);
    expect(getByText('WhatsApp')).toBeInTheDocument();
    await userEvent.click(tabs[1]);
    expect(getByText('Operating System')).toBeInTheDocument();
  });
});
