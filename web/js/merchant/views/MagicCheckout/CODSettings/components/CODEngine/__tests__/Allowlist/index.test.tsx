import React from 'react';
import { render, screen, waitFor, server, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import CODEngineAllowlistUpload from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist';
import { fetchAllowlist } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/__tests__/Allowlist/mocks/handlers';

import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as AllowlistActions from 'merchant/reducers/magicCheckout/codEngineAllowlistUpload/actions';
import { FETCH_SUCCESS_RESPONSE, INPUT_FIELDS, INIT_STATE } from './mocks/fixtures';

const openModalSpy = jest.spyOn(ModalActions, 'openModal');
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
const fetchListSpy = jest.spyOn(AllowlistActions, 'fetchAllowlist');

const renderApp = ({ state = {}, ...props }: Record<string, any> = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <CODEngineAllowlistUpload {...props} />
    </Provider>,
  );
};

describe('testing COD engine allowlist component', () => {
  beforeEach(() => {
    openModalSpy.mockClear();
    showNotificationSpy.mockClear();
    fetchListSpy.mockClear();
  });

  test('component should render properly', async () => {
    renderApp();
    expect(screen.getByTestId('spinner')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByText('No Zipcode list Set!')).toBeInTheDocument();
    });
  });

  test('show the records uploaded', async () => {
    server.use(fetchAllowlist(FETCH_SUCCESS_RESPONSE));
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(123456)).toBeInTheDocument();
    });
  });

  test.each(INPUT_FIELDS)('should be able to type in input fields', async (field) => {
    renderApp();
    const fieldElement = screen.getByPlaceholderText(new RegExp(field.placeholderText, 'i'));
    if (field.placeholderText === 'Enter count') {
      await userEvent.clear(fieldElement);
    }

    await userEvent.type(fieldElement, field.value);
    expect(fieldElement).toHaveAttribute('value', field.value);
  });

  test('should be able to clear the search and reset the pagination', async () => {
    server.use(fetchAllowlist(FETCH_SUCCESS_RESPONSE));
    renderApp();

    await waitFor(() => {
      expect(screen.getByText(123456)).toBeInTheDocument();
    });

    const clearCta = screen.getByRole('button', {
      name: 'Clear',
    });

    await userEvent.click(clearCta);

    await waitFor(() => {
      expect(fetchListSpy).toHaveBeenCalled();
    });
  });

  test('file upload modal should open when clicked on upload allowlist', async () => {
    server.use(fetchAllowlist(FETCH_SUCCESS_RESPONSE));
    renderApp();

    await waitFor(() => {
      expect(
        screen.getByRole('button', {
          name: 'Delete Zipcodes',
        }),
      ).toBeInTheDocument();
    });

    const uploadCta = screen.getByRole('button', {
      name: 'Upload Zipcodes',
    });

    await userEvent.click(uploadCta);

    await waitFor(() => {
      expect(showNotificationSpy).toHaveBeenCalled();
      expect(openModalSpy).toHaveBeenCalled();
    });
  });

  test('delete confirmation modal open when clicking on delete allowlist', async () => {
    server.use(fetchAllowlist(FETCH_SUCCESS_RESPONSE));
    renderApp();

    await waitFor(() => {
      expect(
        screen.getByRole('button', {
          name: 'Delete Zipcodes',
        }),
      ).toBeInTheDocument();
    });

    const deleteCta = screen.getByRole('button', {
      name: 'Delete Zipcodes',
    });

    await userEvent.click(deleteCta);

    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });
});
