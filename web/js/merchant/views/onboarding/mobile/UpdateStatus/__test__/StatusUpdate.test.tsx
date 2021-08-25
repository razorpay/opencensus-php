import React from 'react';
import { StatusUpdate } from '../index';
import { render, screen, waitForElementToBeRemoved } from 'test-utils';
import * as ActivationDB from '../../services/data/ActivationDB';
import * as PaymentEscalationDB from '../../services/data/PaymentEscalationDB';
import * as ActivationDataPieces from '../../services/data/pieces';
import useActivation from '../../hooks/useActivation';
import useEscalation from '../../hooks/useEscalation';

const waitForLoadingToFinish = () =>
  waitForElementToBeRemoved(() => screen.queryByText('Loading...'));

const App: React.FC = () => {
  const { status } = useActivation();
  const { status: escalationsStatus } = useEscalation();

  if (status === 'loading' || escalationsStatus === 'loading') return <div>Loading...</div>;
  return <StatusUpdate />;
};

test('Show updated status only if limit is not breached', async () => {
  ActivationDB.update({
    ...ActivationDataPieces.ActivationFlowWW,
    ...ActivationDataPieces.PaymentEnable,
    ...ActivationDataPieces.OnboardingMileStoneL1,
  });
  PaymentEscalationDB.update({
    updated_at: 1625570425,
  });
  render(<App />, {});
  await waitForLoadingToFinish();
  expect(screen.getByText('Payment volume last updated')).toBeInTheDocument();
});
