import Shimmer from 'common/components/Shimmer';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import {
  CardComponent,
  CardHeader,
  CardItems,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/SectionCard/styled';
import { SHIMMER_CONFIG } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/constants';
import { SectionCardPropsInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import React from 'react';

const ShimmerLayout = ({ isMobile }: Pick<SectionCardPropsInterface, 'isMobile'>): JSX.Element => {
  return (
    <CardComponent data-testid="skeleton-card-shimmer">
      <CardHeader>
        <Shimmer height="32px" width="32px" variant="circular" />
        <Shimmer height="22px" width="210px" variant="rounded" />
      </CardHeader>
      {!isMobile && <Divider noMargin />}
      <CardItems isShimmer>
        {SHIMMER_CONFIG.map((each, index) => {
          return <Shimmer height="16px" width={each} key={`shimmer_${index}`} variant="rounded" />;
        })}
      </CardItems>
    </CardComponent>
  );
};

export default ShimmerLayout;
