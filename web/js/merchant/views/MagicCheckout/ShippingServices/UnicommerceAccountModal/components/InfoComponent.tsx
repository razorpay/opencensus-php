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
  PointsWrapper,
  UnicommerceInfoLink,
  HighlightNote,
} from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/styled';

import {
  StyledEllipse,
  StyledEllipseLeft,
  StyledSquare,
  StyledSquareBottom,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/LinkAccountInfoContianer';

import { UNICOMMERCE_INFO_POINTS } from 'merchant/views/MagicCheckout/ShippingServices/UnicommerceAccountModal/constants';

const InfoPoints = () => {
  return (
    <>
      <StyledEllipse />
      <StyledEllipseLeft />
      {UNICOMMERCE_INFO_POINTS.map((infoPoint) => {
        const {
          instructions: { heading, points, links },
        } = infoPoint;
        return (
          <PointsWrapper key={heading}>
            <PointsContainer>
              <div className="row display-flex">
                <HighlightPoint className="pointer-stroke">
                  <Pointer />
                </HighlightPoint>
                <HighlightStroke>
                  <PointerHeading>{heading}</PointerHeading>
                  <PointListContainer>
                    <List>
                      {points.map((point: string, index: number) => (
                        <ListItem key={point}>
                          {point}{' '}
                          {links ? (
                            <UnicommerceInfoLink
                              href={links[index]}
                              target="_blank"
                              rel="noopener noreferer"
                            >
                              {links[index]}
                            </UnicommerceInfoLink>
                          ) : null}
                        </ListItem>
                      ))}
                    </List>
                  </PointListContainer>
                </HighlightStroke>
              </div>
            </PointsContainer>
          </PointsWrapper>
        );
      })}
      <HighlightNote>
        Note: All your credentials are stored securely and encrypted in our system.
      </HighlightNote>
      <StyledSquare />
      <StyledSquareBottom />
    </>
  );
};

export default InfoPoints;
