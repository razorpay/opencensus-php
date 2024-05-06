import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import SwitchMerchant from 'merchant/components/HeaderNav/SwitchMerchant';
import { noop } from 'lodash';

const props = {
  user: {
    current: {},
    merchants: [
      { id: 1, product: 'primary', name: 'Test1' },
      { id: 2, product: 'banking', name: 'Test2' },
    ],
  },
  onSwitchMerchant: () => noop,
};

describe('SwitchMerchant', () => {
  beforeEach(() => {
    window.rzp_user = {};
  });
  it('should render the component', () => {
    render(<SwitchMerchant {...props} />);
    expect(screen.getByText('Switch Merchant')).toBeInTheDocument();
  });
  // TODO: Fix this in a follow-up
  // error comes from the external library. React-power-select
  xit('should show only primary account options in the dropdown', () => {
    render(<SwitchMerchant {...props} />);
    fireEvent(
      screen.getByText('Switch Merchant'),
      new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
      }),
    );
    const test2 = screen.queryByText('Test2');
    expect(test2).toBeNull();
  });
});
