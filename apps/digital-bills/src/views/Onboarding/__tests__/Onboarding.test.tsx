import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import Onboarding from '@apps/digital-bills/src/views/Onboarding';

describe('Onboarding', () => {
  test('should render Onboarding page', async () => {
    const { getByText, queryByText, getByRole } = renderWithWrappers(
      <Onboarding isInWaitlist={false} />,
    );

    // Landing page
    expect(getByText('BillMe')).toBeInTheDocument();

    // Features page redirection
    await userEvent.click(getByRole('button', { name: /Read More/ }));
    expect(queryByText('BillMe')).not.toBeInTheDocument();
    expect(getByText('What makes BillMe great?')).toBeInTheDocument();

    // Landing page redirection
    await userEvent.click(getByRole('button', { name: /Back/ }));
    expect(queryByText('What makes BillMe great?')).not.toBeInTheDocument();
    expect(getByText('BillMe')).toBeInTheDocument();
  });

  test("should render Features page, when 'isInWaitlist' prop is true", () => {
    const { getByText, queryByText, queryByRole } = renderWithWrappers(<Onboarding isInWaitlist />);
    expect(queryByText('BillMe')).not.toBeInTheDocument();
    expect(getByText('What makes BillMe great?')).toBeInTheDocument();
    expect(queryByRole('button', { name: /Back/ })).not.toBeInTheDocument();
  });
});
