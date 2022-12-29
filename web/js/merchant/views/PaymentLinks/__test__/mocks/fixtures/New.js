import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import New from 'merchant/views/PaymentLinks/PaymentLinks/New';

jest.mock('merchant/views/PaymentLinks/PaymentLinks/CreateV2', () => () => (
  <div className="PaymentLinks--CreateV2">Payment Links V2</div>
));

jest.mock('merchant/views/PaymentLinks/PaymentLinks/Create', () => () => (
  <div className="PaymentLinks--Create Wizard">Payment Links V1</div>
));

export const App = (props) => {
  return <New {...props} />;
};
