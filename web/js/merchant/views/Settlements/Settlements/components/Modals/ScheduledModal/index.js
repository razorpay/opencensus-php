import React, { useState, useEffect } from 'react';
import styled, { createGlobalStyle } from 'styled-components';

import PreEnable from './PreEnable';
import PostEnable from './PostEnable';
import { getInstantPricingPercentage } from './utils';
import { DEFAULT_PRICING_RATE } from './constants';

const GlobalStyles = createGlobalStyle`
  .Modal.Modal--small {
    max-width: 376px;
    width: 376px;
    background-color: transparent;
  }
`;

const Container = styled.div`
  width: 100%;
`;

export default function ScheduledModal({ enabled, postModalType }) {
  const [autoEnabled, setAutoEnabled] = useState(enabled || false);
  const [pricingRate, setPricingRate] = useState(DEFAULT_PRICING_RATE);

  useEffect(() => {
    getInstantPricingPercentage().then(({ data }) => {
      const pricingPercentage = data?.items?.[0]?.pricing_rule?.percent_rate;
      if (pricingPercentage) setPricingRate(pricingPercentage);
    });
  }, []);

  return (
    <>
      <GlobalStyles />
      <Container className="enable-sameday-settlements-modal">
        {autoEnabled ? (
          <PostEnable pricingRate={pricingRate} postModalType={postModalType} />
        ) : (
          <PreEnable pricingRate={pricingRate} setAutoEnabled={setAutoEnabled} />
        )}
      </Container>
    </>
  );
}
