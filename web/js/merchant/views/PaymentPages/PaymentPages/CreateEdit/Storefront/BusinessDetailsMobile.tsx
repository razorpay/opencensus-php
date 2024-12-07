import React from 'react';
import { connect } from 'react-redux';
import {
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
  Box,
  Button,
} from '@razorpay/blade/components';
import { FormDetails } from './BusinessDetailsDrawer';

interface IBusinessDetailsMobile {
  handleClose: () => void;
  entity: {
    contactEmail: string;
    contactPhone: string;
    terms?: string;
    title: string;
  };
  openBuisnessDetailsDrawer: boolean;
  onSubmit: () => void;
  formState: any;
  errors: any;
  handleChange: (e) => void;
}

const BusinessDetailsMobile = ({
  handleClose,
  openBuisnessDetailsDrawer,
  entity,
  onSubmit,
  formState,
  errors,
  handleChange,
}: IBusinessDetailsMobile): React.ReactElement => {
  const { contactEmail, contactPhone } = formState;

  return (
    <BottomSheet
      isOpen={openBuisnessDetailsDrawer}
      onDismiss={handleClose}
      zIndex={10000}
      snapPoints={[0.9, 0.9, 1]}
    >
      <BottomSheetHeader
        subtitle="Indicates required information"
        title="Add your business details"
      />
      <BottomSheetBody>
        <FormDetails
          state={formState}
          entity={entity}
          handleChange={handleChange}
          errors={errors}
          textAreaHeight={4}
        />
      </BottomSheetBody>
      <BottomSheetFooter>
        <Box display="flex" alignItems="center" gap="spacing.5">
          <Box flex={1}>
            <Button
              isFullWidth
              size="medium"
              type="button"
              variant="secondary"
              key="cancel"
              onClick={handleClose}
            >
              Cancel
            </Button>
          </Box>
          <Box flex={1}>
            <Button
              isFullWidth
              size="medium"
              type="button"
              variant="primary"
              key="submit"
              onClick={onSubmit}
              isDisabled={!(contactPhone && contactEmail)}
            >
              Save
            </Button>
          </Box>
        </Box>
      </BottomSheetFooter>
    </BottomSheet>
  );
};

const mapStateToProps = (state) => ({
  entity: state.paymentPageStorefront.entity,
});

export default connect(mapStateToProps)(BusinessDetailsMobile);
