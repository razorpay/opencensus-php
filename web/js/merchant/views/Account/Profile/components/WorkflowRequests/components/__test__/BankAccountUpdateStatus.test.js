import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BankAccountUpdateStatus from '../BankAccountUpdateStatus';
import { render } from 'test-utils';

describe('BankAccountUpdateStatus', () => {
  const defaultProps = {
    content: 'Please upload clear video of your cancelled cheque.',
  };

  const App = (props) => {
    return <BankAccountUpdateStatus {...defaultProps} {...props} />;
  };

  test('should render warning alert by default', () => {
    const { container } = render(<App />);
    expect(container.querySelector('.Alert--warning')).toBeInTheDocument();
  });
});
