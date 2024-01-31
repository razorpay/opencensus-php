import 'react-dates/initialize';
import React from 'react';
import { render as rootRender, screen, waitFor } from '@testing-library/react';
import ResellerPrograms from 'merchant/views/GCMS/Resellers/ResellerPrograms';
import { GcmsTestWrapper } from 'merchant/views/GCMS/shared/test-utils';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));
jest.mock('react-router-dom', () => ({
  useParams: () => ({ resellerId: 'N91osUDdN9WdO9' }),
}));

describe("GCMS: Reseller's programs", () => {
  it("should render reseller's program list", async () => {
    rootRender(
      <GcmsTestWrapper>
        <ResellerPrograms mode="test" />
      </GcmsTestWrapper>,
    );

    await waitFor(() => {
      expect(screen.getByText('Thank You Gift Card')).toBeInTheDocument();
    });
  });
});
