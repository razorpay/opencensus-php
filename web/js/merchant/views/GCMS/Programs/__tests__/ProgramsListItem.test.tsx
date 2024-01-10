import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { render as rootRender, screen } from '@testing-library/react';

import ProgramsListItem from 'merchant/views/GCMS/Programs/ProgramsListItem';

import { programsResponse } from './mocks/fixtures';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

describe('GCMS: Programs', () => {
  it('should render programs details page', () => {
    rootRender(
      <BladeProvider themeTokens={paymentTheme}>
        {/* @ts-expect-error */}
        <ProgramsListItem program={programsResponse.data.items[0]} />
      </BladeProvider>,
    );

    expect(screen.getByText('Inactive Gift Card')).toBeInTheDocument();
    expect(screen.getByText('Denomination')).toBeInTheDocument();
  });
});
