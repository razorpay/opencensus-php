import { render, screen, userEvent, waitFor, server } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { rest } from 'msw';
import CODOrderAutomation from 'merchant/views/MagicCheckout/CODOrderAutomation';
import {
  MOCK_FETCH_PAYLOAD,
  MOCK_FETCH_ERROR_PAYLOAD,
  NO_CONFIG_SET_STATE,
  NULL_RULE_CONFIG_STATE,
  INIT_STATE,
  ALL_CONFIGS_SET_STATE,
  DROPDOWN_INPUTS,
} from 'merchant/views/MagicCheckout/CODOrderAutomation/__tests__/mocks/fixtures';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <CODOrderAutomation {...props} />
    </Provider>,
  );
};

describe('COD order automation tab', () => {
  const showNotification = jest.spyOn(NotificationsActions, 'showNotification');
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  test('COD order automation tab should render properly', async () => {
    renderApp();
    await waitFor(() => {
      expect(
        screen.queryByRole('heading', {
          name: /^COD Review Workflow?/i,
        }),
      ).toBeInTheDocument();
    });
  });

  test('should call fetchConfigs if ruleConfigs value is null', async () => {
    server.use(
      rest.get('*/merchant/api/test/1cc/orders/review/automation/rule_configs', (req, res, ctx) => {
        return res(ctx.status(200), ctx.json(MOCK_FETCH_PAYLOAD), ctx.delay(50));
      }),
    );

    renderApp({ state: NULL_RULE_CONFIG_STATE });
    await waitFor(() => {
      expect(screen.getByText(/Workflow conditions/i)).toBeInTheDocument();
    });
  });

  test('should display error message if fetching configs fails', async () => {
    server.use(
      rest.get('*/merchant/api/test/1cc/orders/review/automation/rule_configs', (req, res, ctx) => {
        return res(ctx.status(200), ctx.json(MOCK_FETCH_ERROR_PAYLOAD), ctx.delay(50));
      }),
    );

    renderApp({ state: NULL_RULE_CONFIG_STATE });

    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: 'Something went wrong, please try again after sometime.',
      });
    });
  });

  test.each(DROPDOWN_INPUTS)(
    'should display dropdowns when no configs has been set yet',
    async (field) => {
      renderApp({ state: NO_CONFIG_SET_STATE });

      const fieldElement = screen.getByTestId(field.testId);
      expect(fieldElement).toBeInTheDocument();

      await userEvent.selectOptions(fieldElement, field.changed_value);

      expect(screen.getByRole('option', { name: field.value }).selected).toBe(true);
    },
  );

  test('should display required when saving empty inputs', async () => {
    renderApp({ state: NO_CONFIG_SET_STATE });

    const fieldElement = screen.getByTestId('action-combobox');
    expect(fieldElement).toBeInTheDocument();

    await userEvent.selectOptions(fieldElement, 'cancel');

    const saveCta = screen.queryByRole('button', {
      name: /Save settings/i,
    });
    expect(saveCta).toBeInTheDocument();

    await userEvent.click(saveCta);
    expect(screen.getByText(/Required/i)).toBeInTheDocument();
  });

  test('should not be able add more config if none is set before', async () => {
    renderApp({ state: NO_CONFIG_SET_STATE });

    const fieldElement = screen.getByTestId('type-combobox');
    expect(fieldElement).toBeInTheDocument();

    await userEvent.selectOptions(fieldElement, 'high');

    const addConfigsCta = screen.getByText(/add more conditions/i);
    expect(addConfigsCta).toBeInTheDocument();

    await userEvent.click(addConfigsCta);
    expect(screen.getByText(/Required/i)).toBeInTheDocument();
  });

  test('should be able to add more config when clicked on add config CTA', async () => {
    renderApp();

    const editCta = screen.getByText('Edit');
    expect(editCta).toBeInTheDocument();
    userEvent.click(editCta);

    await waitFor(() => {
      const addConfigsCta = screen.getByText(/add more conditions/i);
      expect(addConfigsCta).toBeInTheDocument();
      userEvent.click(addConfigsCta);
    });
  });

  test('should be able to remove a config when clicked on remove cta', async () => {
    renderApp();
    const editCta = screen.getByText('Edit');
    expect(editCta).toBeInTheDocument();
    await userEvent.click(editCta);

    let removeCtas = screen.queryAllByTestId('remove-cta');
    expect(removeCtas).toHaveLength(2);

    await userEvent.click(removeCtas[1]);
    removeCtas = screen.queryAllByTestId('remove-cta');
    expect(removeCtas).toHaveLength(1);

    const addConfigCTA = screen.getByText(/add more conditions/i);
    expect(addConfigCTA).toBeInTheDocument();

    await userEvent.click(addConfigCTA);

    await userEvent.click(removeCtas[0]);
    expect(screen.queryAllByText(/Required/i)).not.toBeNull();
  });

  test('should open remove config confimation modal when deleting last config', async () => {
    renderApp();
    const editCta = screen.getByText('Edit');
    expect(editCta).toBeInTheDocument();
    await userEvent.click(editCta);

    let removeCtas = screen.queryAllByTestId('remove-cta');
    expect(removeCtas).toHaveLength(2);

    await userEvent.click(removeCtas[0]);
    removeCtas = screen.queryAllByTestId('remove-cta');
    expect(removeCtas).toHaveLength(1);

    await userEvent.click(removeCtas[0]);
    expect(openModalSpy).toHaveBeenCalled();
  });

  test('should be able to update config when user click on save cta', async () => {
    server.use(
      rest.post(
        '*/merchant/api/test/1cc/orders/review/automation/rule_configs',
        (req, res, ctx) => {
          return res(ctx.status(200), ctx.json(MOCK_FETCH_PAYLOAD), ctx.delay(50));
        },
      ),
    );

    renderApp({ state: ALL_CONFIGS_SET_STATE });
    const editCta = screen.getByText('Edit');
    expect(editCta).toBeInTheDocument();
    await userEvent.click(editCta);

    const removeCtas = screen.queryAllByTestId('remove-cta');
    expect(removeCtas).toHaveLength(3);

    await userEvent.click(removeCtas[0]);

    const saveCta = screen.queryByRole('button', {
      name: /Save settings/i,
    });
    expect(saveCta).toBeInTheDocument();
    userEvent.click(saveCta);

    await waitFor(() => {
      expect(screen.getByText(/Put the order on hold/i)).toBeInTheDocument();
    });
  });

  test('should show notification if something went wrong while updating configs', async () => {
    server.use(
      rest.post(
        '*/merchant/api/test/1cc/orders/review/automation/rule_configs',
        (req, res, ctx) => {
          return res(ctx.status(200), ctx.json(MOCK_FETCH_ERROR_PAYLOAD), ctx.delay(50));
        },
      ),
    );

    renderApp();
    const editCta = screen.getByText('Edit');
    expect(editCta).toBeInTheDocument();
    await userEvent.click(editCta);

    const saveCta = screen.queryByRole('button', {
      name: /Save settings/i,
    });
    expect(saveCta).toBeInTheDocument();
    userEvent.click(saveCta);

    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        type: 'error',
        message: 'Something went wrong, please try again after sometime.',
      });
    });
  });
});
