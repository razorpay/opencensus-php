import React from 'react';
import LineItems from './LineItems';
import { Button, ChevronRightIcon } from '@razorpay/blade/components';
import BusinessDetailsDrawer from './BusinessDetailsDrawer';
interface IAddBusinessDetailsProps {
  handleClick: (val: boolean) => void;
  openBuisnessDetailsDrawer?: boolean;
  isMobile?: boolean;
}

const RightChildren: React.FC<IAddBusinessDetailsProps> = ({ handleClick }) => {
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

const AddBuisnessDetails: React.FC<IAddBusinessDetailsProps> = ({
  handleClick,
  openBuisnessDetailsDrawer,
  isMobile,
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
          isMobile={isMobile}
        />
      )}
    </>
  );
};

export default AddBuisnessDetails;
