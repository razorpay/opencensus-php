import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import {
  render,
  screen,
  server,
  waitFor,
  userEvent,
  cleanup,
} from 'common/services/test/test-utils';
import { rest } from 'msw';
import Details from 'merchant/views/PartnerDashboard/SubMerchant/components/Details';
import {
  bulkResponse,
  productResponse,
  items,
} from 'merchant/views/PartnerDashboard/SubMerchant/__tests__/mocks/fixtures';
import { PRODUCT_TYPE, NOT_AVAILABLE } from 'merchant/views/PartnerDashboard/constants';

const onResendInvite = jest.fn();
const detailsProps = {
  subMerchant: items[1],
  isLoading: false,
  onResendInvite,
  product: PRODUCT_TYPE.CAPITAL,
  capitalProducts: { loading: false, data: productResponse.products },
};

const CapitalResponse =
  bulkResponse.response[items[1].id.replace('acc_', '')].partner_applications[1];

describe('Submerchant Details', () => {
  const renderApp = ({
    subMerchant,
    isLoading = false,
    onResendInvite,
    product,
    capitalProducts,
  }) => {
    return render(
      <Details
        submerchant={subMerchant}
        isLoading={isLoading}
        onResendInvite={onResendInvite}
        product={product}
        capitalProducts={capitalProducts}
      />,
    );
  };
  afterEach(() => {
    cleanup();
  });

  test('should render spinner while data is fetching', () => {
    renderApp({ ...detailsProps, isLoading: true });

    const spinner = screen.getByTestId('spinner');
    expect(spinner).toBeInTheDocument();
  });

  test('should call resendInvite function after clicking invite again button', async () => {
    renderApp({ ...detailsProps });
    await waitFor(() => {
      expect(screen.getByText(items[1].name)).toBeInTheDocument();
    });
    const inviteButton = screen.getByRole('button', { name: /invite again/i });
    expect(inviteButton).toBeInTheDocument();
    await userEvent.click(inviteButton);

    await waitFor(() => {
      expect(onResendInvite).toBeCalled();
    });
  });

  test('should show only sub-merchant details and hide show more button when bulk response throws error', async () => {
    server.use(
      rest.post('*/merchant/api/test/submerchants/capital/applications', (req, res, ctx) => {
        return res(
          ctx.status(400),
          ctx.json({
            status_code: 400,
            success: false,
            data: ['something went wrong!'],
          }),
          ctx.delay(50),
        );
      }),
    );

    renderApp({ ...detailsProps });
    await waitFor(() => {
      expect(screen.getByText(items[1].name)).toBeInTheDocument();
    });
    const showButton = screen.queryByRole('button', { name: /show more details/i });
    expect(showButton).toBeNull();
  });

  test('should show not available when activation status is empty returned by API', async () => {
    renderApp({ ...detailsProps, subMerchant: items[0] });
    await waitFor(() => {
      expect(screen.getByText(items[0].name)).toBeInTheDocument();
    });
    const showButton = screen.getByRole('button', { name: /show more details/i });
    expect(showButton).toBeInTheDocument();
    await userEvent.click(showButton);

    await waitFor(() => {
      expect(screen.getByText('Not Available')).toBeInTheDocument();
    });
  });

  test('should render details when loading is completed', async () => {
    renderApp({ ...detailsProps });
    await waitFor(() => {
      expect(screen.getByText(items[1].name)).toBeInTheDocument();
    });
    expect(screen.getByText(items[1].id)).toBeInTheDocument();
    expect(screen.getByText(items[1].email)).toBeInTheDocument();
    expect(screen.getByText(items[1].user.contact_mobile)).toBeInTheDocument();
    expect(screen.queryByText(CapitalResponse.business_name)).toBeNull();
    const showButton = screen.getByRole('button', { name: /show more details/i });
    expect(showButton).toBeInTheDocument();
    await userEvent.click(showButton);

    await waitFor(() => {
      expect(screen.getByText(CapitalResponse.id)).toBeInTheDocument();
    });
    expect(screen.getByText(CapitalResponse.business_name)).toBeInTheDocument();
    expect(screen.getByText(CapitalResponse.account_name)).toBeInTheDocument();
    expect(screen.getByText(CapitalResponse.stage)).toBeInTheDocument();
    expect(screen.getByText(CapitalResponse.company_address_pincode)).toBeInTheDocument();
    expect(screen.getByText(CapitalResponse.business_type)).toBeInTheDocument();
    expect(screen.getByText(NOT_AVAILABLE)).toBeInTheDocument();
    expect(
      screen.getByText(
        `${CapitalResponse.company_address_line_1} ${CapitalResponse.company_address_line_2} ${CapitalResponse.company_address_city},${CapitalResponse.company_address_state}`,
      ),
    ).toBeInTheDocument();

    const hideButton = screen.getByRole('button', { name: /show less details/i });
    expect(hideButton).toBeInTheDocument();
    await userEvent.click(hideButton);

    await waitFor(() => {
      expect(showButton).toBeInTheDocument();
    });
  });
});
