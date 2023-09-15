import React from 'react';

import Pointer from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/common/Pointer';

import { FormCtaContainer } from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/common';
import {
  PointsContainer,
  PointerHeading,
  PointListContainer,
  List,
  ListItem,
  HighlightPoint,
  HighlightStroke,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/IntegrationPoints';

import { IntegrationPointsPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

const IntegrationPoints = ({
  setStep,
  points,
  pointsHeader,
}: IntegrationPointsPropsType): JSX.Element => {
  return (
    <>
      <PointsContainer>
        <HighlightPoint className="pointer-stroke">
          <Pointer />
        </HighlightPoint>
        <HighlightStroke>
          <PointerHeading>{pointsHeader}</PointerHeading>
          <PointListContainer>
            <List>
              {points.map((point, index) => (
                <ListItem key={index}>{point}</ListItem>
              ))}
            </List>
          </PointListContainer>
        </HighlightStroke>
      </PointsContainer>
      <FormCtaContainer>
        <div className="secondary-cta pointer" onClick={() => setStep((prev: number) => prev + 1)}>
          Skip instructions
        </div>
        <div className="primary-cta pointer" onClick={() => setStep((prev: number) => prev + 1)}>
          Enter credentials <i className="i i-arrow-forward" />
        </div>
      </FormCtaContainer>
    </>
  );
};

export default IntegrationPoints;
