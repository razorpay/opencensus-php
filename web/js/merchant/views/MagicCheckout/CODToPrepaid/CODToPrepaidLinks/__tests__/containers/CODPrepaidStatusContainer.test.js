import { render, screen, userEvent, waitFor, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { rest } from 'msw';
import 'react-dates/initialize';

import CODPrepaidStatusContainer from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/container/CODPrepaidStatusContainer';

import * as ModalActions from 'merchant_common/reducers/modals';

import {
  INIT_STATE,
  FETCH_RESPONSE,
} from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/__tests__/mocks/fixtures';

jest.mock(
  'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/OrderFilters',
  () => (props) => {
    const { onDatesChange, onSubmitHandler, resetHandler } = props;
    return (
      <div>
        <p onClick={() => onDatesChange(1679306325000, 1679824725000)}>Date Range picker</p>
        <button type="button" onClick={onSubmitHandler}>
          Submit
        </button>
        <button
          type="button"
          onClick={() => {
            resetHandler();
            onSubmitHandler();
          }}
        >
          Clear
        </button>
      </div>
    );
  },
);

const renderApp = ({ state = {}, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <CODPrepaidStatusContainer {...props} />
    </Provider>,
  );
};

describe('testing cod prepaid status container component', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  test('COD to prepaid links should render properly', async () => {
    server.use(
      rest.get('*/merchant/api/test/1cc/cod/orders', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            success: true,
            status_code: 200,
            data: {
              items: [],
            },
          }),
          ctx.delay(50),
        );
      }),
    );
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(/No orders found for the selected duration and criteria!/i));
    });
  });

  test('should be able take action on the order', async () => {
    server.use(
      rest.get('*/merchant/api/test/1cc/prepay/orders', (req, res, ctx) => {
        return res(ctx.status(200), ctx.json(FETCH_RESPONSE), ctx.delay(50));
      }),
    );
    renderApp({ state: { ...INIT_STATE, magicCheckout: { cod_order_control: false } } });

    await waitFor(() => {
      expect(screen.getByText('order_JfP6IFRXAaXv9W')).toBeInTheDocument();
    });

    const expireCta = screen.getByTestId('expire-btn');
    await userEvent.click(expireCta);
    expect(openModalSpy).toHaveBeenCalled();
  });

  test('should be able to ascend, descend risk tier and dates', async () => {
    server.use(
      rest.get('*/merchant/api/test/1cc/prepay/orders', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            ...FETCH_RESPONSE,
            data: {
              items: [
                {
                  ...FETCH_RESPONSE.data.items[0],
                  magic_payment_link: { status: 'paid', id: 1234 },
                },
              ],
            },
          }),
          ctx.delay(50),
        );
      }),
    );
    renderApp();

    const ascendCta = screen.getByTestId('date-ascend-arrow');
    const descendCta = screen.getByTestId('riskTier-descend');

    await userEvent.click(ascendCta);
    await userEvent.click(descendCta);

    const expireCta = screen.queryByTestId('expire-btn');
    expect(expireCta).not.toBeInTheDocument();
  });

  test('should able to change date and reset it to original date', async () => {
    server.use(
      rest.get('*/merchant/api/test/1cc/prepay/orders', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            success: true,
            status_code: 200,
            data: {
              items: [],
            },
          }),
          ctx.delay(50),
        );
      }),
    );
    renderApp();
    const dateRangePicker = screen.getByText(/date range picker/i);
    const clearCta = screen.getByRole('button', {
      name: 'Clear',
    });

    await userEvent.click(dateRangePicker);
    await userEvent.click(clearCta);
  });
});
