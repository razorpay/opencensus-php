import React from 'react';

import Pointer from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/common/Pointer';

import {
  ClickpostInfoHeader,
  HighlightPoint,
  HighlightStroke,
  PointerHeading,
  PointsContainer,
  PointerDescription,
} from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/styled';

export const ClickpostInfoComponent = () => {
  return (
    <div>
      <ClickpostInfoHeader>How to connect:</ClickpostInfoHeader>
      <PointsContainer>
        <div className="row display-flex">
          <HighlightPoint className="pointer-stroke">
            <Pointer />
          </HighlightPoint>
          <HighlightStroke>
            <PointerHeading>Clickpost credentials</PointerHeading>
            <PointerDescription>
              Please reach out to the Clickpost team to get your unique username and license key.
            </PointerDescription>
          </HighlightStroke>
        </div>
      </PointsContainer>
    </div>
  );
};

export default ClickpostInfoComponent;
