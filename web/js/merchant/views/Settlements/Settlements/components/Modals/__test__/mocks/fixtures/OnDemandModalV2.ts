import { screen, waitFor } from 'test-utils';

export const waitForOdsModal = async () => {
  await waitFor(() => {
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });
  await waitFor(() => {
    expect(screen.getByText('Instant Settlements')).toBeInTheDocument();
  });
};

export const waitForSuccessScreen = async () => {
  await waitFor(() => {
    expect(screen.getByText(/Settlement Initiated/i)).toBeInTheDocument();
  });
};
