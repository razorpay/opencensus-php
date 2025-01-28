import React from 'react';
import { render, screen, waitFor } from 'test-utils';
import { OnDemandModalEntry } from 'merchant/views/Settlements/Settlements/components/Modals/OnDemandModalEntry';
import * as modals from 'merchant_common/reducers/modals';

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

const renderApp = () => {
  return render(<OnDemandModalEntry />, { showModal: true });
};

describe('Capital/OnDemandModalEntry.test', () => {
  const modalOpenSpy = jest.spyOn(modals, 'openModal');

  afterEach(() => {
    jest.restoreAllMocks();
  });

  test('should render OndemandModal', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('OnDemandV2')).toBeInTheDocument();
    });
    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(modalOpenSpy).not.toHaveBeenCalled();
  });

  test('should render error boundary for ondemand', async () => {
    mockThrowError = 'true';
    renderApp();
    await waitFor(() => {
      expect(screen.getByText("We're sorry — something's gone wrong.")).toBeInTheDocument();
    });
    expect(screen.getByRole('dialog')).toBeInTheDocument();
  });
});
