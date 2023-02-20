import ListContainer from 'merchant/views/PaymentPages/Products';

const defaultProps = {
  paymentPageProductOnBoarding: jest.fn(),
  fetchProducts: jest.fn(),
};

export const App = (props) => {
  return <ListContainer {...defaultProps} {...props} />;
};

export const defaultInitialState = {
  session: {
    user: {
      isAllowedEdit: () => true,
      isOrgAllowedFunctionality: () => true,
    },
  },
  paymentPagesProducts: {
    products: {
      loading: false,
      items: [],
    },
    categories: {
      loading: false,
      items: [],
    },
  },
};
