import PaymentList from 'merchant/views/PaymentButton/PaymentButton/Details/PaymentsList';

const fetchAllMock = jest.fn();

export const App = (props) => {
  return <PaymentList {...props} fetchAll={fetchAllMock} paymentPageId="pnb_123999" />;
};
