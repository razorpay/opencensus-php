import React from 'react';

import { Badge } from "@razorpay/blade/components";

import { BadgeComponentProps } from 'merchant/components/SelfServeRekyc/types';

const BadgeComponent = (props: BadgeComponentProps) => {

  const {
    daysFromDeadline,
    chipType,
    badgeText
  } = props;

  return (
    <Badge
      color={chipType}
      size="medium"
      emphasis="subtle"
    >
      {badgeText ? badgeText : `${daysFromDeadline} days left`}
    </Badge>
  );
};

export default BadgeComponent;