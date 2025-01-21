import React from 'react';
import { Text } from '@razorpay/blade/components';

type EmailCellProps = { email: string };

const EmailCell = ({ email }: EmailCellProps): React.ReactElement => {
  return <Text wordBreak="break-all">{email || '-'}</Text>;
};

export default EmailCell;
