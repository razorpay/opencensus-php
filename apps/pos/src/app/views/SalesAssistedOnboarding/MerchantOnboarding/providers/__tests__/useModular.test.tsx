import React from 'react';
import useModular from '../useModular';
import {
  render,
  server,
  waitForElementToBeRemoved,
  screen,
  userEvent,
  waitFor,
} from 'apps/pos/src/services/test/test-utils';
import {
  getModularConfig,
  updateModularConfig,
} from 'apps/pos/src/services/mocks/handlers/modularConfig';

const checkModularConfig = jest.fn();

const TestApp = ({ merchantId }) => {
  const { isModularLoading, isUpdateModularLoading, modularConfig, updateModularConfig } =
    useModular({
      merchantId,
      onModularConfigUpdate: jest.fn(),
    });

  const handleUpdateModular = () => {
    const payload = {
      test_flag: true,
      modular_callback: () => checkModularConfig('test_callback'),
    };
    updateModularConfig(payload);
  };

  return (
    <React.Fragment>
      {isModularLoading ? <h1> Modular Loading </h1> : null}
      {isUpdateModularLoading ? <h1> Modular Updating </h1> : null}
      <h3>{modularConfig?.workflowData.status === 'executed' ? 'Completed' : 'Pending'}</h3>
      <button onClick={() => checkModularConfig(modularConfig)}>Check Modular Config</button>
      <button onClick={handleUpdateModular}>Test Update Modular</button>
    </React.Fragment>
  );
};

const renderApp = ({ merchantId }) => {
  render(<TestApp merchantId={merchantId} />);
};

describe('useModular', () => {
  test('should return correct modular config on mount and should show modular loading', async () => {
    server.use(getModularConfig({ type: 'success' }));
    renderApp({ merchantId: 'testId' });
    await waitForElementToBeRemoved(screen.getByText('Modular Loading'));
    await userEvent.click(screen.getByText('Check Modular Config'));
    expect(checkModularConfig).toHaveBeenCalledWith(
      expect.objectContaining({
        __typename: 'merchantModularOnboardingDetailsSuccessResponse',
        success: true,
        workflowData: expect.objectContaining({
          id: 'Oc8gV7JlWwXEAr',
        }),
      }),
    );
  });

  test('should show error when modular config fetch fails', async () => {
    server.use(getModularConfig({ type: 'error' }));
    renderApp({ merchantId: 'testId2' });
    await waitForElementToBeRemoved(screen.getByText('Modular Loading'));
    await waitFor(() => {
      expect(screen.getByText('Something went wrong. Please try again.')).toBeInTheDocument();
    });
  });

  test('should call update modular with payload and call callback if passed with loader', async () => {
    server.use(getModularConfig({ type: 'success' }), updateModularConfig({ type: 'success' }));
    renderApp({ merchantId: 'testId3' });
    await waitForElementToBeRemoved(screen.getByText('Modular Loading'));
    await userEvent.click(screen.getByText('Test Update Modular'));
    expect(screen.getByText('Modular Updating')).toBeInTheDocument();
    await waitFor(() => {
      expect(checkModularConfig).toHaveBeenCalledWith('test_callback');
    });
    expect(screen.getByText('Completed')).toBeInTheDocument();
  });

  test('should show error when modular config update fails', async () => {
    server.use(getModularConfig({ type: 'success' }), updateModularConfig({ type: 'error' }));
    renderApp({ merchantId: 'testId4' });
    await waitForElementToBeRemoved(screen.getByText('Modular Loading'));
    await userEvent.click(screen.getByText('Test Update Modular'));
    await waitFor(() => {
      expect(screen.getByText('Something went wrong. Please try again.')).toBeInTheDocument();
    });
  });
});
