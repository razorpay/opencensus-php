import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent, server } from 'common/services/test/test-utils';
import { rest } from 'msw';
import { Provider } from 'react-redux';
import merge from 'lodash/merge';
import store, { storeWithInitialState } from 'merchant/store';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import WhiteLabelTheme from 'merchant/views/PartnerDashboard/Settings/configuration/WhiteLabelTheme';
import {
  config as responseData,
  errorResponse,
  errorResponseFetch,
} from 'merchant/views/PartnerDashboard/Settings/configuration/__tests__/mocks/fixtures';

const storeData = store.getState();
const user = {
  ...storeData.session.user,
  id: 'JFzhDzLxXOgAxM',
};

let showNotificationSpy;

const state = {
  ...storeData,
  session: {
    ...storeData.session,
    user: merge({
      ...storeData.session.user,
      ...user,
    }),
  },
};
describe('WhiteLableTheme', () => {
  const App = ({ initialState = state }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <WhiteLabelTheme />
      </Provider>
    );
  };

  beforeAll(() => {
    showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should render a loader while fetching data from API', () => {
    render(<App />);
    expect(screen.getByTestId('spinner')).toBeVisible();
  });

  // TODO there is a problem with onError callbacks in react-query using msw, will fix this later
  // skipping this for now.
  test.skip('should render an error message, when API throws error and show default values', async () => {
    server.use(
      rest.get('*/merchant/api/test/partner_config', (req, res, ctx) => {
        return res(
          ctx.json({
            ...errorResponseFetch,
          }),
        );
      }),
    );
    render(<App />);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'error',
          message: ['There was an error', 'Status Code: 400'],
        }),
      );
    });

    const clrInput = await screen.findAllByLabelText('brand_color');
    expect(clrInput[0]).toBeInTheDocument();
    expect(clrInput[1]).toBeInTheDocument();
    expect(clrInput[0]).toHaveValue('#528ff0');
    expect(clrInput[1]).toHaveValue('#528FF0');

    const brandLogo = screen.queryByAltText('upload Logo');
    expect(brandLogo).not.toBeInTheDocument();

    const nameInput = await screen.findByLabelText('brand_name');
    expect(nameInput).toBeInTheDocument();
    expect(nameInput).toHaveValue('');
  });

  test('should render the form with values fetched from API', async () => {
    render(<App />);

    const colorInputs = await screen.findAllByLabelText('brand_color');
    expect(colorInputs[0]).toBeInTheDocument();
    expect(colorInputs[1]).toBeInTheDocument();
    expect(colorInputs[0]).toHaveValue(`#${responseData.partner_metadata.brand_color}`);
    expect(colorInputs[1]).toHaveValue(`#${responseData.partner_metadata.brand_color}`);

    const brandName = await screen.findByLabelText('brand_name');
    expect(brandName).toBeInTheDocument();
    expect(brandName).toHaveValue(responseData.partner_metadata.brand_name);

    const brandLogo = await screen.findByAltText('upload Logo');
    expect(brandLogo).toBeInTheDocument();
    expect(brandLogo).toHaveAttribute('src', responseData.partner_metadata.logo_url);
  });

  test('should successfully uploads the logo', async () => {
    render(<App />);
    const file = new File(['(⌐□_□)'], 'chucknorris.png', { type: 'image/png' });
    const uploadLogoBtn = await screen.findByTestId('upInput');
    expect(uploadLogoBtn).toBeInTheDocument();
    await userEvent.upload(uploadLogoBtn, file);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    });

    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'success',
      message: 'Logo Uploaded Successfully',
    });
  });

  test('should show error from API when uploads the logo', async () => {
    server.use(
      rest.post('*/merchant/api/test/partner_config/*/logo', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            ...errorResponse,
          }),
          ctx.delay(50),
        );
      }),
    );
    render(<App />);
    const file = new File(['(⌐□_□)'], 'chucknorris.png', { type: 'image/png' });
    const uploadLogoBtn = await screen.findByTestId('upInput');
    expect(uploadLogoBtn).toBeInTheDocument();
    await userEvent.upload(uploadLogoBtn, file);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    });

    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'error',
      message: ['Sorry, We couldn’t save your changes', 'Status Code: 400'],
    });
  });

  test('should validate and give error message onsubmit', async () => {
    render(<App />);

    const clrInput = await screen.findAllByLabelText('brand_color');
    await userEvent.type(clrInput[1], '#123456');
    userEvent.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Save' })).toBeDisabled();
    });

    expect(clrInput[1]).toHaveValue('#518691#123456');
    expect(screen.getByTestId('clrErr')).toBeVisible();
  });

  test('should show error notification from API while saving config', async () => {
    server.use(
      rest.put('*/merchant/api/test/partner_config/*', (req, res, ctx) => {
        return res(
          ctx.status(200),
          ctx.json({
            ...errorResponse,
          }),
          ctx.delay(50),
        );
      }),
    );
    render(<App />);

    const clrInput = await screen.findAllByLabelText('brand_color');
    await userEvent.clear(clrInput[1]);
    await userEvent.type(clrInput[1], '#123456');

    const nameInput = await screen.findByLabelText('brand_name');
    await userEvent.clear(nameInput);
    await userEvent.type(nameInput, 'Test Brand');

    userEvent.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    });

    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'error',
      message: ['Sorry, We couldn’t save your changes', 'Status Code: 400'],
    });

    expect(clrInput[0]).toHaveValue('#123456');
    expect(clrInput[1]).toHaveValue('#123456');
    expect(nameInput).toHaveValue('Test Brand');
  });

  test('should validate and save the config onsubmit', async () => {
    render(<App />);

    const clrInput = await screen.findAllByLabelText('brand_color');
    await userEvent.clear(clrInput[1]);
    await userEvent.type(clrInput[1], '#123456');

    const nameInput = await screen.findByLabelText('brand_name');
    await userEvent.clear(nameInput);
    await userEvent.type(nameInput, 'Test Brand');

    userEvent.click(screen.getByRole('button', { name: 'Save' }));

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalledTimes(1);
    });

    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'success',
      message: 'Configuration saved Successfully',
    });

    expect(clrInput[0]).toHaveValue('#123456');
    expect(clrInput[1]).toHaveValue('#123456');
    expect(nameInput).toHaveValue('Test Brand');
  });
});
