import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'apps/pos/src/services/test/test-utils';
import PaymentMethodContextProvider from '../PaymentMethodContextProvider';
import PaymentMethodFormComponent from '../PaymentMethodForm';
import { getModularConfig, updateModularConfig } from './mocks/handlers';

jest.setTimeout(30000);

describe.skip('<PaymentMethods/>', () => {
  test('should open bottom sheet on mount if no nach form component is present', async () => {
    server.use(getModularConfig({ type: 'success' }));
    render(<PaymentMethodContextProvider nach component={PaymentMethodFormComponent} />);
    await waitFor(() => {
      expect(screen.getByText(/Select Onboarding Model/i)).toBeInTheDocument();
      expect(screen.getByRole('radio', { name: /Aggregator Model/i })).toBeInTheDocument();
      expect(screen.getByRole('radio', { name: /Direct Model/i })).toBeInTheDocument();
    });
  });

  test('should show MDR rates if aggregator model selected', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'aggregator',
        },
      }),
    );
    render(<PaymentMethodContextProvider nach component={PaymentMethodFormComponent} />);
    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /Aggregator Model/i })).toBeInTheDocument();
    });
    const aggregatorRadio = screen.getByTestId('aggregator-model');
    userEvent.click(aggregatorRadio, { pointerEventsCheck: 0 });
    userEvent.click(screen.getByTestId('acquisition-model-proceed'), { pointerEventsCheck: 0 });
    screen.debug(undefined, 10000000);
    await waitFor(() => {
      expect(screen.getByText('MDR Rates')).toBeInTheDocument();
    });
  });

  test('should not show MDR rates if direct model selected', async () => {
    server.use(
      getModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'direct',
        },
      }),
      updateModularConfig({
        type: 'success',
        data: {
          acquisition_model_field: 'direct',
        },
      }),
    );
    render(<PaymentMethodContextProvider nach component={PaymentMethodFormComponent} />);
    await waitFor(() => {
      expect(screen.getByRole('radio', { name: /Aggregator Model/i })).toBeInTheDocument();
    });
    const aggregatorRadio = screen.getByTestId('aggregator-model');
    userEvent.click(aggregatorRadio);
    userEvent.click(screen.getByTestId('acquisition-model-proceed'));
    screen.debug(undefined, 10000000);
    await waitFor(() => {
      expect(screen.queryByText(/mdr rates/i)).not.toBeInTheDocument();
    });
  });
  test.todo('should show NACH upload form on saving direct model data');
  test.todo('should show NACH upload form on saving aggregator model data');
  test.todo('should disable fields if not editing fields');
  test.todo('should show Save changes when Edit button is clicked');
  test.todo('should show Edit button when Save changes is clicked');
});
