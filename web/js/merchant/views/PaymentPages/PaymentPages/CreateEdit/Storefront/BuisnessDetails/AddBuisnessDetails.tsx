import React from 'react';
import { Button, ChevronRightIcon, Text } from '@razorpay/blade/components';
import BusinessDetailsDrawer from './BusinessDetailsDrawer';
import { connect } from 'react-redux';
import { PaymentPagesStorefrontType } from 'merchant/reducers/paymentPages/storefront';
import LineItems from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/LineItems';

interface IAddBusinessDetailsProps {
  handleClick: (val: boolean) => void;
  openBuisnessDetailsDrawer?: boolean;
  isMobile?: boolean;
  storefront?: PaymentPagesStorefrontType;
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
  storefront,
}) => {
  const isDetailsFilled =
    storefront?.entity?.contactEmail &&
    storefront?.entity?.contactEmail?.length > 0 &&
    storefront?.entity?.contactPhone &&
    storefront?.entity?.contactPhone?.length > 0;
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
          rightChildren={<RightChildren handleClick={handleClick} />}
          isMobile={isMobile}
          isDetailsFilled={Boolean(isDetailsFilled)}
          isMandatoryInfo={true}
        />
      )}
    </>
  );
};

const mapStateToProps = (state: any) => ({
  storefront: state.paymentPageStorefront,
});

export default connect(mapStateToProps)(AddBuisnessDetails);
