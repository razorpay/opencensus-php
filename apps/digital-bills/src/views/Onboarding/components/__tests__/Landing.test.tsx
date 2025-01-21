import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import Landing from '@apps/digital-bills/src/views/Onboarding/components/Landing';

describe('Landing', () => {
  const updateActiveScreen = jest.fn();

  test('should render the Landing page component', () => {
    const { getByText, getByRole } = renderWithWrappers(
      <Landing updateActiveScreen={updateActiveScreen} />,
    );
    expect(getByText('BillMe')).toBeInTheDocument();
    expect(getByText('Say goodbye to paper bills and unlock business growth.')).toBeInTheDocument();
    expect(getByRole('button', { name: /Read More/i })).toBeInTheDocument();
  });

  test("should invoke 'updateActiveScreen' method when 'Read More' button is clicked", async () => {
    const { getByRole } = renderWithWrappers(<Landing updateActiveScreen={updateActiveScreen} />);
    const readMoreBtn = getByRole('button', { name: /Read More/i });
    await userEvent.click(readMoreBtn);
    expect(updateActiveScreen).toHaveBeenCalledTimes(1);
  });
});
