import React from 'react';

import { Button, ChevronRightIcon } from '@razorpay/blade/components';

import LineItems from './LineItems';
import ContactDetailsDrawer from 'merchant/views/PaymentPages/common/Products/ContactDetailsDrawer';

type AddBusinessDetailsProps = {
  handleClick: (val: boolean) => void;
  openBuisnessDetailsDrawer?: boolean;
};

const RightChildren: React.FC<AddBusinessDetailsProps> = ({ handleClick }) => {
  return (
    <Button
      variant="tertiary"
      color="primary"
      size="xsmall"
      icon={ChevronRightIcon}
      onClick={() => handleClick(true)}
    />
  );
};

const AddBuisnessDetails: React.FC<AddBusinessDetailsProps> = ({
  handleClick,
  openBuisnessDetailsDrawer,
}) => {
  return (
    <>
      {openBuisnessDetailsDrawer ? (
        <ContactDetailsDrawer handleClose={() => handleClick(false)} />
      ) : (
        <LineItems
          title="Add business details"
          subTitle="Mandatory information"
          rightChildren={<RightChildren handleClick={handleClick} />}
        />
      )}
    </>
  );
};

export default AddBuisnessDetails;
