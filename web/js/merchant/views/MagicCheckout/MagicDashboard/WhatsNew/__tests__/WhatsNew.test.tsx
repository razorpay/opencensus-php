import React from 'react';
import { render as renderMain } from 'test-utils';
import { storeWithInitialState } from 'merchant/store';

import WhatsNew from 'merchant/views/MagicCheckout/MagicDashboard/WhatsNew';

import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';
import { couponsTitle, quickBuyTitle } from './mocks/NewOffering';
import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

const render = (ui, config = {}) => {
  return renderMain(ui, {
    reduxStore: storeWithInitialState({ ...config }),
  });
};

jest.mock('merchant/views/MagicCheckout/utils/useMagicExperiment', () => ({
  useMagicExperiment: jest.fn(),
}));

describe('WhatsNew Component', () => {
  it('Should render coupons and quickbuy for shopify platform(magic checkout)', () => {
    const initialState = {
      magicCheckout: {
        platform: PLATFORMS.SHOPIFY,
        rcod: false,
      },
    };

    const { getByText } = render(<WhatsNew />, initialState);

    expect(getByText('What’s new in Magic Checkout')).toBeInTheDocument();
    expect(getByText(couponsTitle)).toBeInTheDocument();
    expect(getByText(quickBuyTitle)).toBeInTheDocument();
  });

  it('Should render coupons & Checkout360 title but should not render quickbuy for shopify platform(magicX)', () => {
    const initialState = {
      magicCheckout: {
        platform: PLATFORMS.SHOPIFY,
        rcod: true,
      },
    };
    (useMagicExperiment as jest.Mock).mockReturnValue(true);
    const { getByText, queryByText } = render(<WhatsNew />, initialState);

    expect(getByText('What’s new in Checkout360')).toBeInTheDocument();
    expect(getByText(couponsTitle)).toBeInTheDocument();
    expect(queryByText(quickBuyTitle)).not.toBeInTheDocument();
  });

  it('Should not render coupons and should render quickbuy for native platform', () => {
    const initialState = {
      magicCheckout: {
        platform: PLATFORMS.NATIVE,
        rcod: false,
      },
    };

    const { getByText, queryByText } = render(<WhatsNew />, initialState);

    expect(getByText('What’s new in Magic Checkout')).toBeInTheDocument();
    expect(queryByText(couponsTitle)).not.toBeInTheDocument();
    expect(getByText(quickBuyTitle)).toBeInTheDocument();
  });

  it('Should not render coupons and should render quickbuy for wooc platform', () => {
    const initialState = {
      magicCheckout: {
        platform: PLATFORMS.WOOC,
        rcod: false,
      },
    };

    const { getByText, queryByText } = render(<WhatsNew />, initialState);

    expect(getByText('What’s new in Magic Checkout')).toBeInTheDocument();
    expect(queryByText(couponsTitle)).not.toBeInTheDocument();
    expect(getByText(quickBuyTitle)).toBeInTheDocument();
  });
});
