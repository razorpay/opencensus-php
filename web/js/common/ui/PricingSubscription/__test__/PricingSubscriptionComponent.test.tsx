import React, { Suspense } from 'react';
import { render, screen, userEvent, server, waitFor, getByRole } from 'test-utils';

import PricingSubscriptionComponent from 'common/ui/PricingSubscription/PricingSubscriptionComponent';
import { pricing_bundle } from 'common/ui/PricingSubscription/__test__/PricingParentComponentsMockData';
import {
  getState,
  templateId,
  getMonthlyDiscount,
} from 'common/ui/PricingSubscription/__test__/mock/fixtures';
import { fetchGSModalHandler } from 'common/ui/PricingSubscription/__test__/mock/handlers';
import { PRICING_BUNDLE_VARIANT } from 'merchant/models/GrowthService/growthServiceCTAHandler';
import * as growthServiceReducer from 'merchant/reducers/growthService';
import * as capitalUtils from 'merchant/views/Capital/utils';
import * as modalReducer from 'merchant_common/reducers/modals';

describe('Tests for `PricingSubscriptionComponent` components', () => {
  const renderApp = ({ props = {}, initialState = {} }) =>
    render(
      <Suspense fallback={null}>
        <PricingSubscriptionComponent pricingSubscription={[{ ...pricing_bundle }]} {...props} />
      </Suspense>,
      {
        initialState,
      },
    );

  // UTs with data from props

  test('`Data from props`: Should show the correct header information', () => {
    const initialState = getState();
    renderApp({ initialState });

    expect(screen.getByText(pricing_bundle.header.title)).toBeInTheDocument();
    expect(screen.getByText(pricing_bundle.header.pillText)).toBeInTheDocument();
  });

  test.each(pricing_bundle.featureIdOrder.slice(0, 2))(
    'Data from props`: Should show the correct plan information',
    (featureId) => {
      const initialState = getState();
      renderApp({ initialState });
      pricing_bundle.pricingPlans.forEach((pricingPlan) => {
        expect(screen.getByText(pricingPlan.title)).toBeInTheDocument();
        expect(screen.getAllByText(pricingPlan.button.label)).not.toHaveLength(0);
        expect(
          screen.getByText(`${pricingPlan.monthlyPrice.toLocaleString()}/Month`),
        ).toBeInTheDocument();
        expect(
          screen.getByText(
            `₹${Math.floor(pricingPlan.annualPrice / 12).toLocaleString()}/Month with Annual Plan`,
          ),
        ).toBeInTheDocument();
      });

      expect(
        screen.getByText(pricing_bundle.featureIdToFeatureCopyMap[featureId]),
      ).toBeInTheDocument();
    },
  );

  test('`Data from props`: Should show the correct footer information', () => {
    const initialState = getState();
    renderApp({ initialState });

    expect(screen.getByText('🎁 View All Benefits')).toBeInTheDocument();
  });

  test.each(pricing_bundle.featureIdOrder)(
    '`Data from props`: Should show the full plan information if `View All Benefits` cta is clicked',
    async (featureId) => {
      const initialState = getState();
      renderApp({ initialState });

      const viewAllBenefitsCTA = screen.getByRole('button', {
        name: '🎁 View All Benefits',
      });

      await userEvent.click(viewAllBenefitsCTA);

      pricing_bundle.pricingPlans.forEach((pricingPlan) => {
        expect(screen.getByText(pricingPlan.title)).toBeInTheDocument();
        expect(screen.getAllByText(pricingPlan.button.label)).not.toHaveLength(0);
        expect(
          screen.getByText(`${pricingPlan.monthlyPrice.toLocaleString()}/Month`),
        ).toBeInTheDocument();
        expect(
          screen.getByText(
            `₹${Math.floor(pricingPlan.annualPrice / 12).toLocaleString()}/Month with Annual Plan`,
          ),
        ).toBeInTheDocument();
      });

      expect(
        screen.getByText(pricing_bundle.featureIdToFeatureCopyMap[featureId]),
      ).toBeInTheDocument();
    },
  );

  test.each(pricing_bundle.pricingPlans)(
    '`Data from props`: Should show information about yearly plans if toggled as such',
    async (pricingPlan) => {
      const initialState = getState();
      renderApp({ initialState });

      const frequencyToggle = screen.getByRole('checkbox');
      await userEvent.click(frequencyToggle);

      const yearlyPricing = screen.getByText(`${pricingPlan.annualPrice.toLocaleString()}/Year`);

      expect(yearlyPricing).toBeInTheDocument();

      expect(
        screen.getByText(
          `₹${getMonthlyDiscount(
            pricingPlan.monthlyPrice,
            pricingPlan.annualPrice,
          ).projectedPrice.toLocaleString()}`,
        ),
      ).toBeInTheDocument();

      expect(
        screen.getAllByText(
          `${
            getMonthlyDiscount(pricingPlan.monthlyPrice, pricingPlan.annualPrice).percentSavings
          }% Off`,
        ),
      ).not.toHaveLength(0);

      expect(
        screen.queryByText(
          `₹${Math.floor(pricingPlan.annualPrice / 12).toLocaleString()}/Month with Annual Plan`,
        ),
      ).not.toBeInTheDocument();
    },
  );

  test('`Data from props`: Should close the modal when close icon button is clicked', async () => {
    const closeModalSpy = jest.spyOn(modalReducer, 'closeModal');
    const initialState = getState();
    renderApp({ initialState });

    const notInterestedButton = screen.getByTestId('close-icon');

    await userEvent.click(notInterestedButton);

    expect(closeModalSpy).toHaveBeenCalledTimes(1);
  });

  test.each(pricing_bundle.pricingPlans)(
    '`Data from props`: Should hide the payment buttons when in read only mode',
    (pricingPlan) => {
      const initialState = getState();
      renderApp({ initialState, props: { variant: PRICING_BUNDLE_VARIANT.READ_ONLY } });

      expect(screen.queryByAltText(pricingPlan.button.label)).toBeNull();
    },
  );

  // UTs with data from API using template id

  test('`Data from template id`: Should show a loader if template id is given and the API call is being made', async () => {
    const initialState = getState();
    server.use(fetchGSModalHandler({ delay: 5000 }));
    renderApp({ props: { templateId }, initialState });

    await waitFor(() => expect(screen.getByTestId('spinner')).toBeInTheDocument());
  });

  test('`Data from template id`: Should call fetch template API once on mount', async () => {
    const fetchGSModalSpy = jest.spyOn(growthServiceReducer, 'fetchGSModal');
    const initialState = getState();
    renderApp({ props: { templateId }, initialState });

    server.use(fetchGSModalHandler({ delay: 0, shouldReturnEmptyData: true }));

    await waitFor(() => expect(fetchGSModalSpy).toHaveBeenCalledTimes(1));
    waitFor(() => expect(fetchGSModalSpy).toHaveBeenCalledWith({ template_id: templateId }));
  });

  test('`Data from template id`: Should close modal if empty response', async () => {
    const closeModalSpy = jest.spyOn(modalReducer, 'closeModal');
    const initialState = getState();
    renderApp({ props: { templateId }, initialState });

    server.use(fetchGSModalHandler({ delay: 0, shouldReturnEmptyData: true }));

    await waitFor(() => expect(closeModalSpy).toHaveBeenCalledTimes(1));
  });

  test('`Data from template id`: Should show the correct header information', async () => {
    const initialState = getState();

    renderApp({ props: { templateId }, initialState });

    server.use(fetchGSModalHandler({ delay: 0 }));

    await waitFor(() => expect(screen.getByText(pricing_bundle.header.title)).toBeInTheDocument());
    expect(screen.getByText(pricing_bundle.header.pillText)).toBeInTheDocument();
  });

  test.each(pricing_bundle.featureIdOrder.slice(0, 2))(
    '`Data from template id`: Should show the correct plan information',
    async (featureId) => {
      const initialState = getState();

      renderApp({ props: { templateId }, initialState });

      server.use(fetchGSModalHandler({ delay: 0 }));

      await waitFor(() =>
        expect(
          screen.getByText(pricing_bundle.featureIdToFeatureCopyMap[featureId]),
        ).toBeInTheDocument(),
      );
      pricing_bundle.pricingPlans.forEach((pricingPlan) => {
        expect(screen.getByText(pricingPlan.title)).toBeInTheDocument();
        expect(screen.getAllByText(pricingPlan.button.label)).not.toHaveLength(0);
        expect(
          screen.getByText(`${pricingPlan.monthlyPrice.toLocaleString()}/Month`),
        ).toBeInTheDocument();
        expect(
          screen.getByText(
            `₹${Math.floor(pricingPlan.annualPrice / 12).toLocaleString()}/Month with Annual Plan`,
          ),
        ).toBeInTheDocument();
      });
    },
  );

  test('`Data from template id`: Should show the correct footer information', async () => {
    const initialState = getState();

    renderApp({ props: { templateId }, initialState });

    server.use(fetchGSModalHandler({ delay: 0 }));

    await waitFor(() => expect(screen.getByText('🎁 View All Benefits')).toBeInTheDocument());
  });

  test('`Data from template id`: Should show the full plan information if `View All Benefits` cta is clicked', async () => {
    const initialState = getState();

    renderApp({ props: { templateId }, initialState });

    server.use(fetchGSModalHandler({ delay: 0 }));

    const viewAllBenefitsCTA = await waitFor(() =>
      screen.getByRole('button', {
        name: '🎁 View All Benefits',
      }),
    );
    pricing_bundle.featureIdOrder.forEach(async (featureId) => {
      await waitFor(() =>
        expect(
          screen.getByText(pricing_bundle.featureIdToFeatureCopyMap[featureId]),
        ).toBeInTheDocument(),
      );

      pricing_bundle.pricingPlans.forEach(async (pricingPlan) => {
        await waitFor(() => expect(screen.getByText(pricingPlan.title)).toBeInTheDocument());

        await expect(screen.getAllByText(pricingPlan.button.label)).not.toHaveLength(0);
        await expect(
          screen.getByText(`${pricingPlan.monthlyPrice.toLocaleString()}/Month`),
        ).toBeInTheDocument();
        await expect(
          screen.getByText(
            `₹${Math.floor(pricingPlan.annualPrice / 12).toLocaleString()}/Month with Annual Plan`,
          ),
        ).toBeInTheDocument();
      });
    });
    await userEvent.click(viewAllBenefitsCTA);
  });

  test.each(pricing_bundle.pricingPlans)(
    '`Data from template id`: Should show information about yearly plans if toggled as such',
    async (pricingPlan) => {
      const initialState = getState();

      renderApp({ props: { templateId }, initialState });

      server.use(fetchGSModalHandler({ delay: 0 }));

      const frequencyToggle = await waitFor(() => screen.getByRole('checkbox'));
      await userEvent.click(frequencyToggle);

      const yearlyPricing = screen.getByText(`${pricingPlan.annualPrice.toLocaleString()}/Year`);

      expect(yearlyPricing).toBeInTheDocument();

      expect(
        screen.getByText(
          `₹${getMonthlyDiscount(
            pricingPlan.monthlyPrice,
            pricingPlan.annualPrice,
          ).projectedPrice.toLocaleString()}`,
        ),
      ).toBeInTheDocument();

      expect(
        screen.getAllByText(
          `${
            getMonthlyDiscount(pricingPlan.monthlyPrice, pricingPlan.annualPrice).percentSavings
          }% Off`,
        ),
      ).not.toHaveLength(0);

      expect(
        screen.queryByText(
          `₹${Math.floor(pricingPlan.annualPrice / 12).toLocaleString()}/Month with Annual Plan`,
        ),
      ).not.toBeInTheDocument();
    },
  );

  //TODO: fix this test case in MOBILE pr for settlement balance
  test.skip('fix this test case in MOBILE pr for settlement balance', () => {
    test.each(pricing_bundle.pricingPlans)(
      '`Data from template id`: Should open the Checkout flow when the user clicks on payment button',
      async (pricingPlan) => {
        server.use(fetchGSModalHandler({ delay: 0 }));
        const loadCheckoutScriptSpy = jest.spyOn(capitalUtils, 'loadCheckoutScript');
        const initialState = getState();

        renderApp({ props: { templateId }, initialState });

        const currentPricingColumn = await waitFor(() =>
          screen.getByTestId(`plan-column-${pricingPlan.id}`),
        );
        const paymentButton = await waitFor(() =>
          getByRole(currentPricingColumn, 'button', {
            name: pricingPlan.button.label,
          }),
        );

        await userEvent.click(paymentButton);

        waitFor(() => expect(loadCheckoutScriptSpy).toHaveBeenCalledTimes(1));
      },
    );
  });

  test.each(pricing_bundle.pricingPlans)(
    '`Data from template id`: Should hide the payment buttons when in read only mode',
    (pricingPlan) => {
      server.use(fetchGSModalHandler({ delay: 0 }));
      const initialState = getState();
      renderApp({ initialState, props: { variant: PRICING_BUNDLE_VARIANT.READ_ONLY, templateId } });

      expect(screen.queryByAltText(pricingPlan.button.label)).toBeNull();
    },
  );

  test('`Data from template id`: Should close the modal when close icon button is clicked', async () => {
    server.use(fetchGSModalHandler({ delay: 0 }));

    const closeModalSpy = jest.spyOn(modalReducer, 'closeModal');
    const initialState = getState();
    renderApp({ initialState, props: { templateId } });

    const notInterestedButton = await waitFor(() => screen.getByTestId('close-icon'));

    await userEvent.click(notInterestedButton);

    expect(closeModalSpy).toHaveBeenCalled();
  });
});
