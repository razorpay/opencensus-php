import { render, waitFor, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

import Wrapper from 'common/components/Bootstrap/Wrapper';

import Non3dsCardsActivationResource from 'merchant/models/Non3dsCardsActivation';

// store
import store from 'merchant/store';

// component
import Non3dsCardsActivation from '../Non3dsCardsActivation';

jest.mock('merchant/models/Non3dsCardsActivation');

const context = {
  org: {
    id: 'test_org_id',
  },
  mode: 'test',
};

const renderComponent = () => {
  return render(
    <Wrapper context={context}>
      <Non3dsCardsActivation />
    </Wrapper>,
  );
};

describe('<Non3dsCardsActivation />', () => {
  beforeAll(() => {
    store.dispatch({
      type: 'USER_FETCH::SUCCESS',
      payload: {
        data: {
          role: 'owner',
        },
      },
    });
  });

  test('should render without breaking', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('should show enabled status', async () => {
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: 'approved',
              allow_only_3ds: false,
              updated_at: '10-06-2022',
            },
          }),
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Enabled'));

    expect(screen.getByText('Enabled')).toBeInTheDocument();
  });
  test('should show disabled status', async () => {
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: null,
              allow_only_3ds: true,
              updated_at: '10-06-2022',
            },
          }),
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Request'));

    expect(screen.getByText('Request')).toBeInTheDocument();
  });
  test('should show Request button for workflow failed', async () => {
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: 'failed',
              allow_only_3ds: true,
              updated_at: '10-06-2022',
            },
          }),
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Request'));

    expect(screen.getByText('Request')).toBeInTheDocument();
  });
  test('should show Request button for workflow rejected', async () => {
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: 'rejected',
              allow_only_3ds: true,
              updated_at: '10-06-2022',
            },
          }),
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Request'));

    expect(screen.getByText('Request')).toBeInTheDocument();
  });
  test('should show requested status for workflow open', async () => {
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: true,
              workflow_status: 'open',
              allow_only_3ds: true,
              updated_at: '10-06-2022',
            },
          }),
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Requested'));

    expect(screen.getByText('Requested')).toBeInTheDocument();
  });
  test('should request for enable', async () => {
    const enableRequest = jest.fn(() => Promise.resolve({ success: true }));
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: null,
              allow_only_3ds: true,
              updated_at: '10-07-2022',
            },
          }),
        enable: enableRequest,
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Request'));

    fireEvent.click(screen.getByText('Request'));

    // should open Enable popup
    await waitFor(() => getByText('Enable non 3D Secure Cards'));

    // click on checkbox
    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getAllByText('Request')[1]);

    expect(enableRequest).toHaveBeenCalled();

    const { non3dsCardsActivation } = store.getState();

    expect(non3dsCardsActivation).toMatchObject({
      isEnabling: true,
    });
  });
  test('should request for disable', async () => {
    const disableRequest = jest.fn(() => Promise.resolve({ success: true }));
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: null,
              allow_only_3ds: false,
              updated_at: '10-08-2022',
            },
          }),
        disable: disableRequest,
      };
    });

    const { getByText } = renderComponent();

    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    fireEvent.click(screen.getByText('Learn More'));

    // should open learn more popup
    expect(screen.getAllByText('Support for Non 3D Secure transactions')).toHaveLength(2);

    fireEvent.click(screen.getByText('Disable'));

    expect(disableRequest).toHaveBeenCalled();

    const { non3dsCardsActivation } = store.getState();

    expect(non3dsCardsActivation).toMatchObject({
      isEnabling: true,
    });
  });
  test('should request for enable from learn more popup', async () => {
    const enableRequest = jest.fn(() => Promise.resolve({ success: true }));
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: null,
              allow_only_3ds: true,
              updated_at: '10-07-2022',
            },
          }),
        enable: enableRequest,
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Learn More'));

    fireEvent.click(screen.getByText('Learn More'));

    // should open learn more popup
    expect(screen.getAllByText('Support for Non 3D Secure transactions')).toHaveLength(2);

    fireEvent.click(screen.getByText('Enable'));

    // should open Enable popup
    await waitFor(() => getByText('Enable non 3D Secure Cards'));

    // click on checkbox
    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getAllByText('Request')[1]);

    expect(enableRequest).toHaveBeenCalled();

    const { non3dsCardsActivation } = store.getState();

    expect(non3dsCardsActivation).toMatchObject({
      isEnabling: true,
    });
  });
  test('should show error message on enable failure', async () => {
    const enableRequest = jest.fn(() => Promise.reject({ errors: ['Enablement failed'] }));
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: null,
              allow_only_3ds: true,
              updated_at: '10-07-2022',
            },
          }),
        enable: enableRequest,
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Request'));

    fireEvent.click(screen.getByText('Request'));

    // should open Enable popup
    await waitFor(() => getByText('Enable non 3D Secure Cards'));

    // click on checkbox
    fireEvent.click(screen.getByRole('checkbox'));
    fireEvent.click(screen.getAllByText('Request')[1]);

    expect(enableRequest).toHaveBeenCalled();

    expect(store.getState().non3dsCardsActivation).toMatchObject({
      isEnabling: true,
    });

    await waitFor(() => {
      expect(store.getState().non3dsCardsActivation).toMatchObject({
        isEnabling: false,
      });
    });
  });
  test('should show rejection reason with rejected workflow status', async () => {
    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: 'rejected',
              allow_only_3ds: true,
              updated_at: '10-07-2022',
              rejection_reason_message: 'Test rejected',
            },
          }),
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    await waitFor(() => getByText('Request'));

    expect(screen.getByText('Test rejected')).toBeInTheDocument();
  });
  test('should not show request button for normal user', async () => {
    store.dispatch({
      type: 'USER_FETCH::SUCCESS',
      payload: {
        data: {
          role: 'normal',
        },
      },
    });

    Non3dsCardsActivationResource.mockImplementation(() => {
      return {
        status: () =>
          Promise.resolve({
            status_code: 200,
            success: true,
            data: {
              workflow_exists: false,
              workflow_status: '',
              allow_only_3ds: true,
              updated_at: '10-07-2022',
            },
          }),
      };
    });

    const { getByText } = renderComponent();
    const el = await waitFor(() => {
      return getByText('Support for Non 3D Secure transactions');
    });

    expect(el).toBeDefined();

    expect(screen.getByText('Disabled')).toBeInTheDocument(0);
  });
});
