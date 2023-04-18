import React from 'react';
import { DowntimeSummaryTileStyled } from 'merchant/views/EcosystemDowntimes/styles';
import { Heading, Text } from '@razorpay/blade/components';

type DowntimeSummaryTileType = {
  description: string;
  subText: string;
  value: string | number;
  isMobile: boolean;
};

const DowntimeSummaryTile = ({
  description,
  subText,
  value,
  isMobile,
}: DowntimeSummaryTileType): JSX.Element => {
  return (
    <DowntimeSummaryTileStyled>
      <div className="description-container" aria-label="summary-tile-description">
        <Text size={isMobile ? 'small' : 'medium'}>{description}</Text>
        <Text type="subdued" size="small">
          {subText}
        </Text>
      </div>
      <div aria-label="summary-tile-value">
        {isMobile ? (
          <Text size="medium" weight="bold">
            {value}
          </Text>
        ) : (
          <Heading size="medium" weight="bold">
            {value.toString()}
          </Heading>
        )}
      </div>
    </DowntimeSummaryTileStyled>
  );
};

export default DowntimeSummaryTile;
