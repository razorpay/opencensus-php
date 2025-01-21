import React from 'react';

import StoreFilterModal from '@apps/digital-bills/src/common/components/StoreFilterModal/StoreFilterModal';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import { SELECTED_STORES } from '@apps/digital-bills/src/common/components/StoreFilterModal/__tests__/mocks';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-expect-error
window.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
}));

jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');
  return {
    ...original,
    useQuery: jest.fn().mockReturnValue({
      data: {},
    }),
  };
});

describe('StoreFilterModal', () => {
  test("should render 'StoreFilterModal' component as expected", async () => {
    const closeModal = jest.fn();
    const onStoreSelect = jest.fn();

    const { getByText, getByRole } = renderWithWrappers(
      <StoreFilterModal
        isOpen={true}
        dismiss={closeModal}
        onStoresSelect={onStoreSelect}
        selectedStores={SELECTED_STORES}
      />,
    );
    expect(getByText('Store Filter')).toBeInTheDocument();
    expect(getByText('Stores')).toBeInTheDocument();

    const cancelBtn = getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    await userEvent.click(cancelBtn);
    expect(closeModal).toHaveBeenCalledTimes(1);

    const submitBtn = getByRole('button', { name: 'Submit' });
    expect(submitBtn).toBeInTheDocument();
    await userEvent.click(submitBtn);
    expect(onStoreSelect).toHaveBeenCalledTimes(1);
    expect(closeModal).toHaveBeenCalledTimes(2);
  });
});
