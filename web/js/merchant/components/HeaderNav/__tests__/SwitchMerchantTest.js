import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import SwitchMerchant, {
  CREATE_MERCHANT_CTA_LABEL,
} from 'merchant/components/HeaderNav/SwitchMerchant';
import { noop } from 'lodash';

const props = {
  user: {
    current: {},
    merchants: [
      { id: 1, product: 'primary', name: 'Test1', billing_label: 'billing label 1' },
      { id: 2, product: 'banking', name: 'Test2', billing_label: 'billing label 2' },
      { id: 2, product: 'primary', name: '', billing_label: 'billing label 3' },
    ],
  },
  onSwitchMerchant: () => noop,
};

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      create_merchant_cta: {
        variables: {
          result: 'off',
        },
      },
    },
  }),
  withSplitzService: jest.fn(),
}));

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
    expect(screen.getByText(CREATE_MERCHANT_CTA_LABEL)).toBeVisible();
  });

  xit('should show billing label options in the dropdown if merchant feature flag "submerchant_dba_name" is enabled', () => {
    const updatedAppProps = { ...props };
    updatedAppProps.user.isSubMerchantDBANameEnabled = true;
    render(<SwitchMerchant {...updatedAppProps} />);
    fireEvent(
      screen.getByText('Switch Merchant'),
      new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
      }),
    );

    const billingLabel = screen.getAllByText(/billing label 1/i);
    expect(billingLabel[0]).toBeInTheDocument();
  });

  xit('should show Existing Account label for accounts with no name in the dropdown', () => {
    render(<SwitchMerchant {...props} />);
    fireEvent(
      screen.getByText('Switch Merchant'),
      new MouseEvent('click', {
        bubbles: true,
        cancelable: true,
      }),
    );
    expect(screen.queryByText('Existing Account 1')).toBeVisible();
  });

  xit('should show create account button if experiment is on', () => {
    jest.doMock('common/splitz', () => ({
      useSplitzService: () => ({
        abExperiments: {
          create_merchant_cta: {
            variables: {
              result: 'on',
            },
          },
        },
      }),
    }));

    const openSpy = jest.spyOn(window, 'open').mockImplementation(() => {});

    render(<SwitchMerchant {...props} />);

    expect(screen.getByText(CREATE_MERCHANT_CTA_LABEL)).toBeVisible();

    fireEvent.click(screen.getByText(CREATE_MERCHANT_CTA_LABEL));

    expect(openSpy).toHaveBeenCalledWith('https://accounts.np.razorpay.in/merchants/new', '_blank');
  });

  xit('should not show create account button if experiment is off', () => {
    render(<SwitchMerchant {...props} />);

    expect(screen.getByText(CREATE_MERCHANT_CTA_LABEL)).not.toBeVisible();
  });
});
