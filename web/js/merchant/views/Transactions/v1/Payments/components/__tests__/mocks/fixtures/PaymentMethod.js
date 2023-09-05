import PaymentMethod from 'merchant/views/Transactions/v1/Payments/components/PaymentMethod';

export const defaultProps = {
  payment: {},
};

export const defaultUpiPayment = {
  method: 'upi',
  amount: 20000,
};

export const upiTransferDetails = {
  details: {
    virtual_account: {
      description: 'UPI description',
    },
    virtual_account_id: 'qwee23234vdsv',
    payer_vpa: 'payer@vpa',
  },
};

export const App = (props) => {
  return <PaymentMethod {...defaultProps} {...props} />;
};
