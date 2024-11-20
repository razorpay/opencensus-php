import React from 'react';
import { Badge } from '@razorpay/blade/components';

const RouteNewTag = () => {
  return (
    <Badge
      color="positive"
      key="positive"
      emphasis="subtle"
      size="small"
      marginLeft="spacing.3"
      position="relative"
      top="-2px"
    >
      New
    </Badge>
  );
};

export default RouteNewTag;
