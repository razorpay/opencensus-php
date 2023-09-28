import React from 'react';
import { render, userEvent } from 'test-utils';
import PaymentButtonsCard from 'merchant/containers/Home/ProductOnboardingCard/partials/PaymentButtonsCard';
import * as analytics from 'common/utils/analytics';
import { createMemoryHistory } from 'history';

let analyticsTrackSpy, history;

const renderApp = (component, options) => {
  return render(component, {
    ...options,
    renderViaRouteGuard: false,
  });
};

describe('ProductOnboardingCard - PaymentButtonsCard', () => {
  beforeAll(() => {
    analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrack');

    history = createMemoryHistory();
    history.push = jest.fn();
  });
  beforeEach(() => {
    jest.clearAllMocks();
  });
  test('should render component without errors', () => {
    expect(() =>
      renderApp(<PaymentButtonsCard history={history} product="PG" />),
    ).not.toThrowError();
  });

  test('should navigate and trigger event on CTA click', async () => {
    const { getByRole } = renderApp(<PaymentButtonsCard history={history} product="PH" />);
    const cta = getByRole('button');

    expect(cta).toBeEnabled();
    await userEvent.click(cta);

    expect(history.push).toHaveBeenCalledTimes(1);
    expect(analyticsTrackSpy).toHaveBeenCalledTimes(1);
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'Dashboard PB', properties: { product: 'PH' } }),
    );
  });
});
