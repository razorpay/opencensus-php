// testing utils
import { render, fireEvent, waitFor, userEvent, screen } from 'test-utils';

// router
import { MemoryRouter as Router, Route } from 'react-router-dom';

import Wrapper from 'common/components/Bootstrap/Wrapper';

// component
import ListTable from 'merchant/views/Transactions/v1/B2bPayments/components/ListTable';

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
    renderComponent({ items: [], loading: true });
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });
  test('Should render payment in table', () => {
    renderComponent({ items: testTxn, uploadState: {} });
    expect(screen.getByText('Captured')).toBeInTheDocument();
  });
  test('Should show view action if payment has invoice id', () => {
    renderComponent({ items: testTxn, uploadState: {} });
    expect(screen.getByText('VIEW')).toBeInTheDocument();
  });
  test('Should show upload button if payment status is authorized and invoice id is null', () => {
    renderComponent({
      items: testTxn.map((txn) => ({ ...txn, b2b_export_invoice: null, status: 'authorized' })),
      uploadState: {},
    });
    expect(screen.getByText('Upload')).toBeInTheDocument();
  });
  test('Should trigger upload invoice', async () => {
    const onUpload = jest.fn();
    renderComponent({
      items: testTxn.map((txn) => ({ ...txn, b2b_export_invoice: null, status: 'authorized' })),
      uploadState: {},
      onUpload,
    });
    const file = new File(['(⌐□_□)'], 'testImage.png', { type: 'image/png' });
    await userEvent.click(screen.getByText('Upload'));

    await waitFor(() =>
      // TODO: debug the issue, why userEvent not able to triggered upload file on the input
      fireEvent.change(screen.getByTestId('b2b-file-uploader'), {
        target: {
          files: [file],
        },
      }),
    );
    expect(onUpload).toHaveBeenCalled();
    expect(onUpload).toHaveBeenCalledWith('id', file);
  });

  test('should trigger callback for add buyer address', async () => {
    const onBuyerAddressClick = jest.fn();

    renderComponent({
      items: testTxn.map((txn) => ({ ...txn, b2b_export_invoice: null, status: 'authorized' })),
      uploadState: {},
      onBuyerAddressClick,
    });

    await userEvent.click(screen.getByText('Add/Update Buyer Address'));
    expect(onBuyerAddressClick).toHaveBeenCalledWith('id');
  });

  test('should not show upload and add buyer address buttons if payment captured', () => {
    renderComponent({
      items: testTxn,
      uploadState: {},
    });

    expect(screen.queryByText('Upload')).not.toBeInTheDocument();
    expect(screen.queryByText('Add/Update Buyer Address')).not.toBeInTheDocument();
    expect(screen.getByText('VIEW')).toBeInTheDocument();
  });

  test('should show add buyer address button if invoice is uploaded and payment is authorized', () => {
    renderComponent({
      items: testTxn.map((txn) => ({
        ...txn,
        b2b_export_invoice: 'doc_randomId',
        status: 'authorized',
      })),
      uploadState: {},
    });

    expect(screen.queryByText('Upload')).not.toBeInTheDocument();
    expect(screen.getByText('Add/Update Buyer Address')).toBeInTheDocument();
    expect(screen.getByText('VIEW')).toBeInTheDocument();
  });
});
