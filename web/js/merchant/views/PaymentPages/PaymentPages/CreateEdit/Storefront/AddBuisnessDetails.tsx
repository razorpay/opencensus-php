import React from 'react';

import { Button, ChevronRightIcon } from '@razorpay/blade/components';

import LineItems from './LineItems';
import BusinessDetailsDrawer from './BusinessDetailsDrawer';

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
        <BusinessDetailsDrawer
          handleClose={() => handleClick(false)}
          openBuisnessDetailsDrawer={openBuisnessDetailsDrawer}
        />
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
