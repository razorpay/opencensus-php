import Details from 'merchant/views/PaymentButton/PaymentButton/Details';

const defaultProps = {
  match: {
    params: { id: 'pl_LDzywAOTf36CYX' },
  },
};

export const App = () => {
  return <Details {...defaultProps} />;
};
