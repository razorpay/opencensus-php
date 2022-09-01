// testing utils
import { render, fireEvent, waitFor } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

// router
import { MemoryRouter as Router, Route } from 'react-router-dom';

import Wrapper from 'common/components/Bootstrap/Wrapper';

// component
import ListTable from '../components/ListTable';

const context = {
  org: {
    id: 'test_org_id',
  },
  mode: 'test',
};

const testTxn = [
  {
    id: 'id',
    entity: 'payment',
    amount: 20000,
    currency: 'INR',
    base_amount: 20000,
    status: 'captured',
    order_id: null,
    invoice_id: null,
    international: true,
    method: 'app',
    amount_refunded: 0,
    amount_transferred: 0,
    refund_status: null,
    captured: false,
    description: null,
    card_id: null,
    bank: null,
    vpa: null,
    email: 'email@razorpay.com',
    contact: '+918888888888',
    notes: [],
    fee: null,
    tax: null,
    error_code: 'SERVER_ERROR',
    error_description:
      'We are facing some trouble completing your request at the moment. Please try again shortly.',
    error_source: 'internal',
    error_step: 'payment_initiation',
    error_reason: 'server_error',
    acquirer_data: {
      discount: 0,
      amount: 200,
    },
    created_at: 1655472637,
    provider: 'poli',
    b2b_export_invoice: 'doc',
  },
];

const renderComponent = (props) => {
  return render(
    <Wrapper context={context}>
      <Router initialEntries={['/']}>
        <Route path="/" component={(routeProps) => <ListTable {...routeProps} {...props} />} />
      </Router>
    </Wrapper>,
  );
};

describe('Test <ListTable />', () => {
  test('Should render without breaking', () => {
    expect(renderComponent({ items: [] })).toBeDefined();
  });
  test('Should show loading state', () => {
    const { container } = renderComponent({ items: [], loading: true });
    expect(container.querySelector('.spinner')).toBeInTheDocument();
  });
  test('Should render payment in table', () => {
    const { getByText } = renderComponent({ items: testTxn, uploadState: {} });
    expect(getByText('Captured')).toBeInTheDocument();
  });
  test('Should show view action if payment has invoice id', () => {
    const { getByText } = renderComponent({ items: testTxn, uploadState: {} });
    expect(getByText('View')).toBeInTheDocument();
  });
  test('Should show upload button if payment status is authorized and invoice id is null', () => {
    const { getByText } = renderComponent({
      items: testTxn.map((txn) => ({ ...txn, b2b_export_invoice: null, status: 'authorized' })),
      uploadState: {},
    });
    expect(getByText('Upload')).toBeInTheDocument();
  });
  test('Should upload the invoice', async () => {
    const onUpload = jest.fn();
    const { getByText, container } = renderComponent({
      items: testTxn.map((txn) => ({ ...txn, b2b_export_invoice: null, status: 'authorized' })),
      uploadState: {},
      onUpload,
    });
    const file = new File(['(⌐□_□)'], 'testImage.png', { type: 'image/png' });
    fireEvent.click(getByText('Upload'));
    await waitFor(() =>
      fireEvent.change(container.querySelector('.b2b-file-uploader'), {
        target: {
          files: [file],
        },
      }),
    );
    expect(onUpload).toHaveBeenCalled();
    expect(onUpload).toHaveBeenCalledWith('id', file);
  });
});
