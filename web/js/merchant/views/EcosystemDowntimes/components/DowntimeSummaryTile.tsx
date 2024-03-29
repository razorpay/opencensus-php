import React from 'react';
import { Text } from '@razorpay/blade/components';

import { DowntimeSummaryTileStyled } from 'merchant/views/EcosystemDowntimes/styles';

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
        <Text size="small" color="surface.text.gray.muted">
          {subText}
        </Text>
      </div>
      <div aria-label="summary-tile-value">
        {isMobile ? (
          <Text size="small" weight="semibold">
            {value}
          </Text>
        ) : (
          <Text weight="semibold" size="large">
            {value.toString()}
          </Text>
        )}
      </div>
    </DowntimeSummaryTileStyled>
  );
};

export default DowntimeSummaryTile;
