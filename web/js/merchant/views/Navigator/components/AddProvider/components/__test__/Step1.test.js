import React from 'react';
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

import { deepClone } from 'common/utils/rzp-utils';
import { Step1 } from 'merchant/views/Navigator/components/AddProvider/components/Step1';

describe('Step 1 Screen', () => {
  let mockProps;

  beforeEach(() => {
    mockProps = {
      isEdit: false,
      steps: {
        1: {
          edit: true,
          show: true,
        },
      },
      providers: {
        payu: {
          'Gateway Name': { data_value: 'PayU' },
          'Payment Methods': {
            data_value: ['card', 'upi', 'netbanking', 'emi', 'wallet', 'emandate', 'sodexo'],
          },
          optimizer_seamless_disabled: false,
        },
        paytm: {
          'Gateway Name': { data_value: 'PayTm' },
          'Payment Methods': {
            data_value: ['card', 'upi', 'netbanking', 'wallet'],
          },
          optimizer_seamless_disabled: false,
        },
        checkout_dot_com_optimizer: {
          'Gateway Name': { data_value: 'Checkout.com' },
          'Payment Methods': {
            data_value: ['card'],
          },
        },
        optimizer_razorpay: {
          'Gateway Name': { data_value: 'Razorpay' },
          'Gateway Acquirer': {
            data_value: [
              { name: 'Axis Bank', value: 'axis_vas' },
              { name: 'HDFC Bank', value: 'hdfc_vas' },
              { name: 'ICICI Bank', value: 'icici_vas' },
            ],
          },
          'Payment Methods': { data_value: ['card', 'upi', 'netbanking'] },
        },
      },
      loadingProviders: false,
      selectedProvider: null,
    };
  });

  it('should render Step1 without any errors', () => {
    expect(() => <Step1 {...mockProps} />).not.toThrowError();
  });

  describe('List Providers', () => {
    it('should render provider list spinner', () => {
      const { queryByTestId } = render(<Step1 {...mockProps} loadingProviders={true} />);
      expect(queryByTestId('spinner')).toBeInTheDocument();
    });

    it('should render the list of Popular & All Gateways', () => {
      render(<Step1 {...mockProps} />);
      expect(screen.getByText('Popular Gateways')).toBeInTheDocument();
      expect(screen.getByText('All Gateways')).toBeInTheDocument();
      expect(screen.getAllByTestId('gateway-provider')).toHaveLength(5);
      expect(screen.getByText('PayTm')).toBeInTheDocument();
      expect(screen.queryAllByText(/PayU/)).toHaveLength(2);
    });
  });

  describe('Search Provider', () => {
    it('should render Gateway label', () => {
      const { getByText } = render(<Step1 {...mockProps} />);
      expect(getByText('Gateway')).toBeInTheDocument();
    });

    it('should render search gateway input', () => {
      render(<Step1 {...mockProps} />);
      const searchInput = screen.getByLabelText('Search Gateway');
      expect(searchInput).toBeInTheDocument();
    });

    it('should update search state when input value is changed', async () => {
      render(<Step1 {...mockProps} />);
      const searchInput = screen.getByLabelText('Search Gateway');
      expect(searchInput).toBeInTheDocument();

      await act(async () => {
        await userEvent.type(searchInput, 'Pro');
      });

      await waitFor(() => {
        expect(searchInput).toHaveValue('Pro');
      });
    });

    it('should render filter list of gateways by serch term', async () => {
      render(<Step1 {...mockProps} />);
      const searchInput = screen.getByLabelText('Search Gateway');
      expect(searchInput).toBeInTheDocument();

      await act(async () => {
        await userEvent.type(searchInput, 'Pay');
      });

      await waitFor(() => {
        expect(searchInput).toHaveValue('Pay');
        expect(screen.getAllByTestId('gateway-provider')).toHaveLength(2);
        expect(screen.getByText('PayU')).toBeInTheDocument();
        expect(screen.getByText('PayTm')).toBeInTheDocument();
      });
    });

    it('should render no gateways for wrong search term', async () => {
      render(<Step1 {...mockProps} />);
      const searchInput = screen.getByLabelText('Search Gateway');
      expect(searchInput).toBeInTheDocument();

      await act(async () => {
        await userEvent.type(searchInput, 'zap');
      });

      await waitFor(() => {
        expect(searchInput).toHaveValue('zap');
        expect(screen.getByText('No Providers Found')).toBeInTheDocument();
      });
    });
  });

  describe('Selected Provider', () => {
    it('should render selected gateway', () => {
      const { getByText, getAllByTestId } = render(
        <Step1 {...mockProps} selectedProvider="payu" />,
      );

      expect(getAllByTestId('selected-gateway')).toHaveLength(1);
      expect(getByText('PayU')).toBeInTheDocument();
      expect(getByText('Change Gateway')).toBeInTheDocument();
      expect(getByText(/Integration type/)).toBeInTheDocument();
    });

    it('should render selected gateway - readOnly', () => {
      const props = {
        ...deepClone(mockProps),
        steps: { 1: { edit: false, show: true } },
        selectedProvider: 'payu',
        isEdit: true,
      };

      const { getByText, getAllByTestId, queryByText } = render(<Step1 {...props} />);

      expect(getAllByTestId('provider-readOnly')).toHaveLength(1);
      expect(getByText('PayU')).toBeInTheDocument();
      expect(queryByText('Change Gateway')).not.toBeInTheDocument();
    });

    it('should render Checkout.com in list of All Gateways', () => {
      render(<Step1 {...mockProps} />);
      expect(screen.getByText('All Gateways')).toBeInTheDocument();
      expect(screen.getByText('Checkout.com')).toBeInTheDocument();
    });

    it('should render selected gateway', () => {
      const { getByText, getAllByTestId, queryByText } = render(
        <Step1 {...mockProps} selectedProvider="checkout_dot_com_optimizer" />,
      );

      expect(getAllByTestId('selected-gateway')).toHaveLength(1);
      expect(getByText('Checkout.com')).toBeInTheDocument();
      expect(getByText('Change Gateway')).toBeInTheDocument();
      expect(queryByText(/Enable seamless option/)).not.toBeInTheDocument();
    });

    it('should render Razorpay in list of All Gateways', () => {
      render(<Step1 {...mockProps} />);
      expect(screen.getByText('All Gateways')).toBeInTheDocument();
      expect(screen.getByText('Razorpay')).toBeInTheDocument();
    });

    it('should render Razorpay as selected gateway', async () => {
      const { getByText, getAllByTestId } = render(
        <Step1
          {...mockProps}
          selectedProvider="optimizer_razorpay"
          changeGatewayDetails={() => jest.fn()}
        />,
      );

      expect(getAllByTestId('selected-gateway')).toHaveLength(1);
      expect(getByText('Razorpay')).toBeInTheDocument();
      expect(getByText('Change Gateway')).toBeInTheDocument();
      expect(getByText(/Account type/)).toBeInTheDocument();

      const bankingVasRadio = screen.getByLabelText('Banking VAS');
      expect(bankingVasRadio).toBeInTheDocument();

      await act(async () => {
        await userEvent.click(bankingVasRadio);
      });

      await waitFor(() => {
        expect(screen.getByLabelText(/Bank/)).toBeInTheDocument();
      });

      const bankList = screen.getByPlaceholderText('Select bank');
      expect(bankList).toBeInTheDocument();
      await act(async () => {
        await userEvent.click(bankList);
      });

      const axisBank = screen.getByText('Axis Bank');
      expect(axisBank).toBeInTheDocument();
      await act(async () => {
        await userEvent.click(axisBank);
      });

      expect(bankList).toHaveValue('Axis Bank');
    });
  });
});
