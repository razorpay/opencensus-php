import React, { useState } from 'react';
import { Button, TrashIcon } from '@razorpay/blade/components';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent, waitFor } from '@apps/digital-bills/src/services/test/test-utils';
import DeleteModal from '@apps/digital-bills/src/views/BillDetails/containers/BillDetailContainer/components/DeleteModal';

describe('DeleteModal', () => {
  function App(): React.ReactElement {
    const [isOpen, setIsOpen] = useState<boolean>(false);
    return (
      <>
        <Button
          icon={TrashIcon}
          variant="tertiary"
          onClick={(): void => setIsOpen(true)}
          testID="delete-btn"
        />
        <DeleteModal
          onDelete={() => undefined}
          modalProps={{ isOpen, onDismiss: () => setIsOpen(false) }}
        />
      </>
    );
  }
  test('should render the DeleteModal component', async () => {
    const { getByTestId, getByText, getByRole } = renderWithWrappers(<App />);
    const trashBtn = getByTestId('delete-btn');
    await userEvent.click(trashBtn);
    expect(getByText('Heads Up!')).toBeInTheDocument();
    expect(getByRole('button', { name: 'Delete' })).toBeInTheDocument();
    expect(getByRole('button', { name: 'Cancel' })).toBeInTheDocument();
  });

  test('should render the delete modal and closes it', async () => {
    const { getByTestId, getByText, queryByText } = renderWithWrappers(<App />);
    const trashBtn = getByTestId('delete-btn');
    await userEvent.click(trashBtn);
    expect(getByText('Heads Up!')).toBeInTheDocument();
    const dismissBtn = getByTestId('dismiss-btn');
    await userEvent.click(dismissBtn);
    await waitFor(() => expect(queryByText('Heads Up!')).toBeNull());
    expect(queryByText('Heads Up!')).toBeNull();
  });
});
