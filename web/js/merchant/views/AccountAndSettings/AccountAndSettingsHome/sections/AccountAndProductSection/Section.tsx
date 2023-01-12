import React from 'react';
import { Heading } from '@razorpay/blade/components';
import { CardContainer, CardContent } from './styled';
import Divider from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/Divider';
import SectionCard from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/SectionCard';
import { isMobileDevice } from 'merchant/components/Home/data';
import { AccountAndProductSectionPropsInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';

const AccountAndProductSection = ({
  isMobile,
  sections,
}: AccountAndProductSectionPropsInterface): JSX.Element => {
  return (
    <CardContainer>
      {!isMobile && <Heading size="small">Account and product settings</Heading>}
      <CardContent>
        {sections?.map((each, index) => {
          return (
            <>
              <SectionCard key={`${each.id}_${index}`} {...each} isMobile={isMobile} />
              {isMobile && sections.length - 1 !== index && <Divider noMargin />}
            </>
          );
        })}
      </CardContent>
    </CardContainer>
  );
};

export default AccountAndProductSection;

AccountAndProductSection.defaultProps = {
  isMobile: isMobileDevice(),
};
