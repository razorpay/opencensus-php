import axios from 'axios';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { render, screen, waitFor, userEvent } from 'test-utils';
import LeadForm from '..';
import { reqPayload } from './mocks/constants';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';

const onSubmit = jest.fn(() => true);
const closeModal = jest.spyOn(ModalActions, 'closeModal');
const showNotification = jest.spyOn(NotificationsActions, 'showNotification');

const user = {
  user: {
    email: 'xyz@xyz.com',
  },
  current: '10000000000000',
};

jest.mock('common/utils/cookies', () => ({
  getCookie: jest.fn(() => 'hutkcookie'),
}));

jest.mock('axios');

const reqArgs = {
  method: 'post',
  baseURL:
    'https://api.hsforms.com/submissions/v3/integration/submit/5558946/ce788171-a866-4dbc-8623-e29d3bcddc4b',
  headers: {
    'Content-type': 'application/json',
  },
};

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <LeadForm {...props} />
    </Provider>,
  );
};

describe('LeadForm component tests', () => {
  test('should render all the fields', async () => {
    renderApp({ props: { onSubmit }, state: { session: { user } } });
    await waitFor(() => {
      expect(screen.getByText(/First name/i)).toBeInTheDocument();
      expect(screen.getByText(/Phone number/i)).toBeInTheDocument();
      expect(screen.getByText(/Website URL/i)).toBeInTheDocument();
      expect(screen.getByText(/What tech stack is your website built on/i)).toBeInTheDocument();
      expect(screen.getByText(/Do you offer COD/i)).toBeInTheDocument();
    });
  });

  test('should submit the form', async () => {
    renderApp({ props: { onSubmit }, state: { session: { user } } });
    axios.mockResolvedValueOnce([]);

    const nameField = screen.getByTestId('firstname');
    await userEvent.type(nameField, 'xyz');

    const phoneField = screen.getByTestId('phone');
    await userEvent.type(phoneField, '9999999991');

    const urlField = screen.getByTestId('url');
    await userEvent.type(urlField, 'https://xyz.com');

    const submitCta = screen.queryAllByRole('button')[1];
    await userEvent.click(submitCta);
    expect(axios).toHaveBeenCalledWith({
      ...reqArgs,
      data: reqPayload,
    });
    expect(closeModal).toHaveBeenCalled();
  });

  test('should show notification on submit error', async () => {
    renderApp({ props: { onSubmit }, state: { session: { user } } });
    axios.mockRejectedValueOnce([]);

    const nameField = screen.getByTestId('firstname');
    await userEvent.type(nameField, 'xyz');

    const phoneField = screen.getByTestId('phone');
    await userEvent.type(phoneField, '9999999991');

    const urlField = screen.getByTestId('url');
    await userEvent.type(urlField, 'https://xyz.com');

    const submitCta = screen.queryAllByRole('button')[1];
    await userEvent.click(submitCta);
    expect(axios).toHaveBeenCalledWith({
      ...reqArgs,
      data: reqPayload,
    });
    expect(showNotification).toHaveBeenCalled();
    expect(closeModal).toHaveBeenCalled();
  });

  test('should not make submit call if reqd fields are empty', async () => {
    renderApp({ props: { onSubmit }, state: { session: { user } } });
    axios.mockRejectedValueOnce([]);

    const nameField = screen.getByTestId('firstname');
    await userEvent.type(nameField, 'xyz');

    const urlField = screen.getByTestId('url');
    await userEvent.type(urlField, 'https://xyz.com');

    const submitCta = screen.queryAllByRole('button')[1];
    await userEvent.click(submitCta);
    expect(showNotification).toHaveBeenCalled();
    expect(axios).not.toHaveBeenCalled();
    expect(closeModal).not.toHaveBeenCalled();
  });
});
