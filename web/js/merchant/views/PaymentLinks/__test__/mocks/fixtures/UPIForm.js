import UPIForm from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/Forms/UPIForm';

jest.mock(
  'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/ReferenceId',
  () => () => {
    return <div>Reference Id</div>;
  },
);

jest.mock(
  'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/LinkExpiry',
  () => () => {
    return <div>Link Expiry</div>;
  },
);

jest.mock(
  'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/Reminders',
  () => () => {
    return <div>Reminders</div>;
  },
);

jest.mock(
  'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/PartialPayments',
  () => () => {
    return <div>PartialPayments</div>;
  },
);

jest.mock(
  'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/DynamicFields',
  () => () => {
    return <div>Dynamic Fields</div>;
  },
);

jest.mock(
  'merchant/views/PaymentLinks/PaymentLinks/CreateV2/components/Fields/Notify',
  () => () => {
    return <div>Notify Fields</div>;
  },
);

jest.mock('common/utils/localStorage', () => ({
  setItem: jest.fn(),
  getItem: jest.fn().mockReturnValue(true),
  removeItem: jest.fn(),
}));

const defaultProps = {
  formData: {
    currency: 'INR',
    amount: '200',
  },
};

export const App = (props = {}) => {
  return <UPIForm {...defaultProps} {...props} />;
};
