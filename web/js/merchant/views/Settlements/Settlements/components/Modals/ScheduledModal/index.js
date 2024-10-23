import React, { useState } from 'react';
import styled, { createGlobalStyle } from 'styled-components';

import PostEnable from './PostEnable';
import PreEnable from './PreEnable';

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

export default function ScheduledModal({ enabled, postModalType, trackSameDaySettlement, from }) {
  const [autoEnabled, setAutoEnabled] = useState(enabled || false);

  return (
    <>
      <GlobalStyles />
      <Container className="enable-sameday-settlements-modal">
        {autoEnabled ? (
          <PostEnable postModalType={postModalType} />
        ) : (
          <PreEnable
            setAutoEnabled={setAutoEnabled}
            trackSameDaySettlement={trackSameDaySettlement}
            from={from}
          />
        )}
      </Container>
    </>
  );
}
