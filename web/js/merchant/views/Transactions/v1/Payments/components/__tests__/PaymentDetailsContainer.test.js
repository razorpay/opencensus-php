import '@testing-library/jest-dom/extend-expect';
import { screen, userEvent, waitFor, server } from 'test-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { renderApp } from 'merchant/views/Transactions/v1/Payments/components/__tests__/mocks/fixtures/PaymentDetailsContainer';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { rest } from 'msw';
import { updateItemInPayments } from 'merchant/reducers/collection';
import ShowWhen from 'merchant/components/ShowWhen';
import { customSettlementEnabled } from 'merchant/views/Settlements/v2/util';

describe('PaymentDetailsContainer', () => {
  const waitForPaymentDetails = async () => {
    await waitFor(() =>
      expect(selfServeTrackSuccess).toHaveBeenCalledWith({
        selfServeAction: 'Payment Details Fetched',
        screen: 'Payment Details',
        props: {},
      }),
    );
    await waitFor(() => expect(screen.getByText('Payment Details')).toBeInTheDocument());
  };

  test('should render payment details with bank_transfer payment method', async () => {
    renderApp();
    await waitForPaymentDetails();
  });

  test('should render payment details with upi payment method', async () => {
    server.use(
      rest.get('*/payments/:paymentId', (req, res, ctx) => {
        return res.once(
          ctx.json({
            status_code: 200,
            data: {
              id: 'pay_1234',
              method: 'upi',
              notes: {},
              transaction: {},
            },
          }),
          ctx.delay(50),
        );
      }),
    );
    renderApp();
    await waitForPaymentDetails();
  });

  describe('Settlement details', () => {
    test('should render payment settlement details', async () => {
      customSettlementEnabled.mockImplementationOnce(() => true);
      renderApp();
      await waitForPaymentDetails();
      await waitFor(() => expect(screen.getByText('Settlement Details')).toBeInTheDocument());
    });

    test('should catch payment settlement details error', async () => {
      customSettlementEnabled.mockImplementationOnce(() => true);
      server.use(
        rest.get('*/org_settlements/:settlementId', (req, res, ctx) => {
          return res.once(ctx.errors(['Some error occurred']), ctx.delay(50));
        }),
      );
      renderApp();
      await waitForPaymentDetails();
      await waitFor(() =>
        expect(
          screen.getByText('Something went wrong, please try again later'),
        ).toBeInTheDocument(),
      );
    });
  });

  describe('Confirmation modal', () => {
    test('should render a confirmation modal to cancel payment capture', async () => {
      renderApp();
      await waitForPaymentDetails();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Confirm Capture',
        }),
      );
      expect(
        screen.getByText('Are you sure you want to capture this payment?'),
      ).toBeInTheDocument();
      await userEvent.click(
        screen.getByRole('button', {
          name: "No, don't!",
        }),
      );
      expect(analyticsTrack).toHaveBeenCalledWith(
        expect.objectContaining({
          objectName: 'capture payment confirmation popup',
          actionName: 'clicked',
          screen: 'home page',
          properties: expect.objectContaining({
            action: 'no',
            location: 'payments',
            paymentId: 'pay_1234',
            paymentMethod: 'bank_transfer',
            paymentStatus: 'authorized',
          }),
        }),
      );
    });

    test('should render a confirmation modal to confirm payment capture', async () => {
      renderApp();
      await waitForPaymentDetails();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Confirm Capture',
        }),
      );
      expect(
        screen.getByText('Are you sure you want to capture this payment?'),
      ).toBeInTheDocument();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Yes, Capture',
        }),
      );
      await waitFor(() => expect(screen.getByText('Payment Captured')).toBeInTheDocument());
    });

    test('should render a confirmation modal to handle payment capture errors ', async () => {
      server.use(
        rest.post('*/payments/:paymentId/capture', (req, res, ctx) => {
          return res(ctx.errors(['Some error occurred']), ctx.delay(50));
        }),
      );
      renderApp();
      await waitForPaymentDetails();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Confirm Capture',
        }),
      );
      expect(
        screen.getByText('Are you sure you want to capture this payment?'),
      ).toBeInTheDocument();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Yes, Capture',
        }),
      );
      await waitFor(() =>
        expect(analyticsTrack).toHaveBeenCalledWith(
          expect.objectContaining({
            objectName: 'capture payment',
            actionName: 'status',
            screen: 'home page',
            properties: expect.objectContaining({
              status: 'failure',
              location: 'payments',
            }),
          }),
        ),
      );
    });
  });

  describe('Go To Link CTA', () => {
    test("should go to provided link when isOpenedInDualMode & link !== 'transfers/new'", async () => {
      const { history } = renderApp({
        props: {
          isOpenedInDualMode: true,
        },
      });
      await waitForPaymentDetails();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Go To Link',
        }),
      );
      expect(history.location.pathname).toBe('/details');
    });

    test('should go to provided link when !entity_name', async () => {
      const { history } = renderApp({
        props: {
          entity_name: null,
        },
      });
      await waitForPaymentDetails();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Go To Link',
        }),
      );
      expect(history.location.pathname).toBe('/payments/pay_1234/details');
    });
  });
  describe('Open refund modal', () => {
    describe('Open refund modal CTA', () => {
      test('should open refund modal ', async () => {
        renderApp();
        await waitForPaymentDetails();
        await userEvent.click(
          screen.getByRole('button', {
            name: 'Open Refund Modal',
          }),
        );
        expect(screen.getByText('Refund Modal')).toBeInTheDocument();
      });
    });

    describe('Payment Refund CTA', () => {
      test('should update item in payments ', async () => {
        renderApp();
        await waitForPaymentDetails();
        await userEvent.click(
          screen.getByRole('button', {
            name: 'Open Refund Modal',
          }),
        );
        await userEvent.click(
          screen.getByRole('button', {
            name: 'Payment Refund',
          }),
        );
        await waitFor(() => expect(updateItemInPayments).toHaveBeenCalled());
      });
    });
  });

  describe('Update Reference Id CTA', () => {
    test('should update item in payments ', async () => {
      renderApp();
      await waitForPaymentDetails();
      await userEvent.click(
        screen.getByRole('button', {
          name: 'Update Reference Id',
        }),
      );
      await waitFor(() => expect(updateItemInPayments).toHaveBeenCalled());
    });
  });

  describe('Secondary View', () => {
    describe('When entityName is disputes', () => {
      test('should render dispute details ', async () => {
        renderApp();
        await waitForPaymentDetails();
        expect(screen.getByText('Dispute Details')).toBeInTheDocument();
      });

      test('should close dispute details when close button is clicked', async () => {
        const { history } = renderApp();
        const historyPushSpy = jest.spyOn(history, 'push');
        await waitForPaymentDetails();
        await userEvent.click(screen.getByRole('button', { name: 'Close Secondary View' }));
        expect(historyPushSpy).toHaveBeenCalledWith('/');
      });
    });

    describe('When entityName is transfers', () => {
      test('should render create new payment transfer view', async () => {
        ShowWhen.mockImplementation(({ children }) => <div>{children}</div>);
        renderApp({
          props: {
            entity_name: 'transfers',
          },
        });
        await waitForPaymentDetails();
        await userEvent.click(screen.getByRole('button', { name: 'Create New Transfer' }));
        await waitFor(() => expect(updateItemInPayments).toHaveBeenCalled());
      });
      test('should close create new payment transfer view when close button is clicked', async () => {
        ShowWhen.mockImplementation(({ children }) => <div>{children}</div>);
        const { history } = renderApp({
          props: {
            entity_name: 'transfers',
          },
        });
        const historyPushSpy = jest.spyOn(history, 'push');
        await waitForPaymentDetails();
        await userEvent.click(screen.getByRole('button', { name: 'Close Secondary View' }));
        expect(historyPushSpy).toHaveBeenCalledWith('/');
      });
    });
  });
});
