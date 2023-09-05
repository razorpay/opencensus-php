import { screen, waitFor } from 'test-utils';

export const expectLoadingToBeVisible = async () => {
  await waitFor(() => {
    expect(screen.getByTestId('loading')).toBeInTheDocument();
  });
};

export const expectLoadingToBeHidden = async () => {
  await waitFor(() => {
    expect(screen.queryByTestId('loading')).not.toBeInTheDocument();
  });
};

export const expectFailedToBeVisible = async () => {
  await waitFor(() => {
    expect(screen.getByTestId('failed')).toBeInTheDocument();
  });
};

export const expectFailedToBeHidden = async () => {
  await waitFor(() => {
    expect(screen.queryByTestId('failed')).not.toBeInTheDocument();
  });
};
