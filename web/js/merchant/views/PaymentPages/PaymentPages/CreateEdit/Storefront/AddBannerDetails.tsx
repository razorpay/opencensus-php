import React from 'react';

import { Button, ChevronRightIcon } from '@razorpay/blade/components';

import LineItems from './LineItems';

const RightChildren = () => {
  return <Button variant="tertiary" color="primary" size="xsmall" icon={ChevronRightIcon} />;
};

const AddBannerDetails = () => {
  return <LineItems title="Add store banner" rightChildren={<RightChildren />} />;
};

export default AddBannerDetails;
