import { Text } from '@razorpay/blade/components';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import SectionCard from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/SectionCard';
import Shimmer from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Shimmer';
import { SHIMMER_SIZE } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/constants';
import { AccountAndProductSectionPropsInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import React from 'react';
import { CardContainer, CardContent } from './styled';

const CardDivider = ({
  isMobile,
  isLastSection,
}: Pick<AccountAndProductSectionPropsInterface, 'isMobile'> & {
  isLastSection: boolean;
}) => {
  return isMobile && !isLastSection ? <Divider noMargin /> : null;
};

const AccountAndProductSection = ({
  isMobile,
  sections,
}: AccountAndProductSectionPropsInterface): JSX.Element => {
  return (
    <CardContainer>
      {!isMobile && <Text size="large">Account and product settings</Text>}
      <CardContent>
        {sections?.length
          ? sections.map((each, index) => (
              <>
                {' '}
                <SectionCard key={`${each.id}_${index}`} {...each} isMobile={isMobile} />
                <CardDivider isMobile={isMobile} isLastSection={sections.length - 1 === index} />
              </>
            ))
          : new Array(SHIMMER_SIZE).fill(null).map((_, index) => (
              <>
                {' '}
                <Shimmer key={`shimmer_${index}`} isMobile={isMobile} />
                <CardDivider isMobile={isMobile} isLastSection={SHIMMER_SIZE - 1 === index} />
              </>
            ))}
      </CardContent>
    </CardContainer>
  );
};

export default AccountAndProductSection;
