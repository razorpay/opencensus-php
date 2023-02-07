import React from 'react';
import { InfoIcon } from '@razorpay/blade/components';
import { StyledCard, CardHeader, CardContent, CardFooter, CardHeaderIcon } from './styledUtils';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';

const SettlementCard = ({ heading, headingInfo, content, footer, customCardStyle }) => {
  return (
    <StyledCard customCardStyle={customCardStyle}>
      <CardHeader>
        {heading}
        {headingInfo && (
          <CardHeaderIcon>
            <InfoIcon color="currentColor" size="small" />
            <PopoverComponent align="top" theme="dark">
              <PopoverBody>{headingInfo}</PopoverBody>
            </PopoverComponent>
          </CardHeaderIcon>
        )}
      </CardHeader>
      <CardContent>{content}</CardContent>
      <CardFooter>{footer}</CardFooter>
    </StyledCard>
  );
};

export default SettlementCard;
