import React from 'react';
import { Text } from '@razorpay/blade/components';

import { DEFAULT_COUNTRY_CODE } from '@apps/digital-bills/src/utils/constants';

type ContactCellProps = {
  contact: string;
  countryCode: string | null;
};

const ContactCell = ({ contact, countryCode }: ContactCellProps): React.ReactElement => {
  return <Text>{contact ? `${countryCode ?? DEFAULT_COUNTRY_CODE} ${contact}` : '-'}</Text>;
};

export default ContactCell;
