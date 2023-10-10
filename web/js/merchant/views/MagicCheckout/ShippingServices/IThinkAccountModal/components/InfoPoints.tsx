import React from 'react';

import Pointer from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/common/Pointer';

import {
  PointsContainer,
  PointerHeading,
  PointListContainer,
  List,
  ListItem,
  HighlightPoint,
  HighlightStroke,
  FormCtaContainer,
  PointsWrapper,
} from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/styled';

import { ITHINK_LOGISTICS_STEP_TEXT } from 'merchant/views/MagicCheckout/ShippingServices/constants';

import { InfoPointsType } from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/types';

const InfoPoints = (props: InfoPointsType) => {
  const { step, setStep } = props;

  const {
    instructions: { heading, points },
    cta: { secondaryStep, secondary, primary },
  } = ITHINK_LOGISTICS_STEP_TEXT[step];

  const handleSecondaryClick = (): void => {
    setStep(secondaryStep);
  };

  const handlePrimaryClick = (): void => {
    setStep(step + 1);
  };

  return (
    <PointsWrapper>
      <PointsContainer>
        <div className="row display-flex">
          <HighlightPoint className="pointer-stroke">
            <Pointer />
          </HighlightPoint>
          <HighlightStroke>
            <PointerHeading>{heading}</PointerHeading>
            <PointListContainer>
              <List>
                {points.map((point: string) => (
                  <ListItem key={point}>{point}</ListItem>
                ))}
              </List>
            </PointListContainer>
          </HighlightStroke>
        </div>
      </PointsContainer>
      <FormCtaContainer>
        <div className="secondary-cta pointer" onClick={handleSecondaryClick}>
          {secondary}
        </div>
        <div className="primary-cta pointer" onClick={handlePrimaryClick}>
          {primary} <i className="i i-arrow-forward" />
        </div>
      </FormCtaContainer>
    </PointsWrapper>
  );
};

export default InfoPoints;
