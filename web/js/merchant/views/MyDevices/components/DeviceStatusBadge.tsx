import React from 'react';
import { Badge, CheckCircle2Icon, XCircleIcon } from '@razorpay/blade/components';

const DeviceStatusBadge = ({ isActive }) => (
  <Badge
    marginY="spacing.3"
    emphasis="intense"
    color={isActive ? 'positive' : 'negative'}
    size="medium"
    icon={isActive ? CheckCircle2Icon : XCircleIcon}
  >
    {isActive ? 'Active' : 'Inactive'}
  </Badge>
);

export default DeviceStatusBadge;
