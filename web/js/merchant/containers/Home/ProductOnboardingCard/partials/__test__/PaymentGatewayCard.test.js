import React from 'react';
import { render, userEvent } from 'test-utils';
import PaymentGatewayCard from 'merchant/containers/Home/ProductOnboardingCard/partials/PaymentGatewayCard';
import * as analytics from 'common/utils/analytics';
import { createMemoryHistory } from 'history';

let analyticsTrackSpy, history;

describe('ProductOnboardingCard - PaymentGatewayCard', () => {
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
      render(<PaymentGatewayCard history={history} product="PH" />, {
        renderViaRouteGuard: false,
      }),
    ).not.toThrowError();
  });

  test('should navigate and trigger event on CTA click', async () => {
    const { getByRole } = render(<PaymentGatewayCard history={history} product="PG" />, {
      renderViaRouteGuard: false,
    });
    const cta = getByRole('button');

    expect(cta).toBeEnabled();
    await userEvent.click(cta);

    expect(history.push).toHaveBeenCalledTimes(1);
    expect(history.push).toHaveBeenCalledWith('/api-keys');
    expect(analyticsTrackSpy).toHaveBeenCalledTimes(1);
    expect(analyticsTrackSpy).toHaveBeenCalledWith(
      expect.objectContaining({ objectName: 'Dashboard PG', properties: { product: 'PG' } }),
    );
  });
});
