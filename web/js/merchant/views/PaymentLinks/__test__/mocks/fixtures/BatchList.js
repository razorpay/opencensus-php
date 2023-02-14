import List from 'merchant/views/PaymentLinks/BatchUpload/List';

const defaultProps = {
  location: {
    search: 'link_type=upi',
  },
};

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);

jest.mock('merchant/views/PaymentLinks/BatchUpload/components/SendAllLinks', () => () => (
  <div>Send All links</div>
));

jest.mock('merchant/views/PaymentLinks/BatchUpload/components/PaymentLinksForm', () => () => (
  <div>Payment link form</div>
));

export const App = (props = {}) => {
  return <List {...defaultProps} {...props} />;
};
