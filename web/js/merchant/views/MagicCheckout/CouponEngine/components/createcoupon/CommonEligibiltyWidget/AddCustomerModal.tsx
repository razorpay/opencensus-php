import React, { useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';

// UI imports
import ListCustomerDetails from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CommonEligibiltyWidget/ListCustomerDetails';
import UploadToUfh from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CommonEligibiltyWidget/UploadNewData/UploadToUfh';

// Helpers imports
import { closeModal } from 'merchant_common/reducers/modals';

interface AddCustomerDetailsProps {
  closeModal: () => void;
  handleSelectedData: (data: any) => void;
}

const AddCustomerDetails: React.FC<AddCustomerDetailsProps> = ({
  closeModal,
  handleSelectedData,
}) => {
  const [shouldShowUploadFileView, setShouldShowUploadFileView] = useState(false);
  const [eligibleCustomersDetails, setEligibleCustomersDetails] = useState<{
    type: string;
    name: string;
    source_type: string;
    id: string;
  }>({
    type: 'phone_number',
    name: '',
    source_type: '',
    id: '',
  });

  return (
    <div>
      {!shouldShowUploadFileView ? (
        <ListCustomerDetails
          closeModal={closeModal}
          setShouldShowUploadFileView={setShouldShowUploadFileView}
          eligibleCustomersDetails={eligibleCustomersDetails}
          setEligibleCustomersDetails={setEligibleCustomersDetails}
          handleSelectedData={handleSelectedData}
        />
      ) : (
        <UploadToUfh
          eligibleCustomersDetails={eligibleCustomersDetails}
          handleSelectedData={handleSelectedData}
          setEligibleCustomersDetails={setEligibleCustomersDetails}
        />
      )}
    </div>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(AddCustomerDetails);
