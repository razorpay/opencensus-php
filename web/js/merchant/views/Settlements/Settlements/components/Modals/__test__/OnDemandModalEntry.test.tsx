import React from 'react';
import { render, screen, waitFor } from 'test-utils';

import { SpiltzContextState } from 'common/splitz/types';
import { OnDemandModalEntry } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandModalEntry';
import * as modals from 'merchant_common/reducers/modals';

jest.mock('merchant/views/Settlements/Settlements/components/Modals/OndemandModal', () => ({
  ...(jest.requireActual(
    'merchant/views/Settlements/Settlements/components/Modals/OndemandModal',
  ) as object),
  __esModule: true,
  default: () => <p>OndemandModal V1</p>,
}));
let mockThrowError = '';

jest.mock('merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/OnDemandV2', () => ({
  ...(jest.requireActual(
    'merchant/views/Settlements/Settlements/components/Modals/OnDemandV2/OnDemandV2',
  ) as object),
  __esModule: true,
  default: () => {
    if (mockThrowError) {
      throw new Error('crash');
    }
    return <p>OnDemandV2</p>;
  },
}));

const variantOn = { capital_is_settle_now_v2: { variables: { result: 'on' } } };
const defaultAbExperiments = {};
let mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments } as unknown as SpiltzContextState),
}));

const renderApp = () => {
  return render(<OnDemandModalEntry />, { showModal: true });
};

describe('Capital/OnDemandModalEntry.test', () => {
  const modalOpenSpy = jest.spyOn(modals, 'openModal');

  afterEach(() => {
    jest.restoreAllMocks();
  });

  test('should render OndemandModalV1 when experiment is off', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('OndemandModal V1')).toBeInTheDocument();
    });
    expect(screen.getByRole('dialog', { name: /modal/i })).toBeInTheDocument();
    expect(modalOpenSpy).toHaveBeenCalledTimes(1);
    expect(modalOpenSpy).toHaveBeenCalledWith(
      expect.objectContaining({ isNew: false, size: 'small', disableClose: true }),
    );
  });

  test('should render OndemandModalV2 when experiment is on', async () => {
    mockAbExperiments = variantOn;
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('OnDemandV2')).toBeInTheDocument();
    });
    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(modalOpenSpy).not.toHaveBeenCalled();
  });

  test('should render error boundary for ondemandv2', async () => {
    mockAbExperiments = variantOn;
    mockThrowError = 'true';
    renderApp();
    await waitFor(() => {
      expect(screen.getByText("We're sorry — something's gone wrong.")).toBeInTheDocument();
    });
    expect(screen.getByRole('dialog')).toBeInTheDocument();
  });
});
