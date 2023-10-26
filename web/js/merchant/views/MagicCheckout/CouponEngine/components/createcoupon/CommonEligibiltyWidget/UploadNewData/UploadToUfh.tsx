import React, { useEffect } from 'react';

// ui imports
import Input from 'common/new-ui/Input';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import ModalCTA from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CommonEligibiltyWidget/UploadNewData/ModalActions';
import { ValidateModalInfo } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CommonEligibiltyWidget/UploadNewData/ValidateInfoModal';
import { FormGroup } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

// api imports
import { uploadCustomerDetailsToUfh } from 'merchant/views/MagicCheckout/CouponEngine/api';

// constants imports
import {
  DISPLAY_MESSAGES_FOR_UFH_MODAL,
  SAMPLE_FILE_URL,
} from 'merchant/views/MagicCheckout/CouponEngine/constants';

interface UploadToUfhProps {
  handleSelectedData: (data: any) => void;
  eligibleCustomersDetails: {
    type: string;
    id: string;
    name: string;
    source_type: string;
  };
  setEligibleCustomersDetails: (details: any) => void;
}

const UploadToUfh: React.FC<UploadToUfhProps> = ({
  handleSelectedData,
  eligibleCustomersDetails,
  setEligibleCustomersDetails,
}) => {
  // Initialize eligibleCustomersDetails when the component mounts
  useEffect(() => {
    setEligibleCustomersDetails((prev) => ({
      ...prev,
      id: '',
      source_type: 'ufh',
    }));
  }, []);

  const uploadToUfh = async (file: File, progressTracker: any) => {
    const data = await uploadCustomerDetailsToUfh(file, progressTracker);
    setEligibleCustomersDetails((prev) => ({
      ...prev,
      source: data.data?.file_id,
      name: file.name,
    }));
    return data;
  };

  return (
    <div>
      <BatchUpload
        accept={['csv']}
        title="Add selected customers"
        batchType="add_customers_data"
        validateBatch={uploadToUfh}
        processFile
        displayMsgs={DISPLAY_MESSAGES_FOR_UFH_MODAL}
        validateModalInfo={<ValidateModalInfo sampleUrl={SAMPLE_FILE_URL} />}
        maxFileSize={52428800} // 50MB
        batchListClass="rto-history-upload"
        component={
          <FormGroup style={{ padding: '0 24px', margin: '28px 0 0', gap: '30px' }}>
            <div className="form-label">Type</div>
            <div className="form-input">
              <Input.Radio
                autoRender
                name="couponEligibility"
                defaultValue={eligibleCustomersDetails.type}
                onChange={(e) => {
                  setEligibleCustomersDetails((prev) => ({
                    ...prev,
                    type: e.target.value,
                  }));
                }}
                options={[
                  {
                    label: 'Mobile Number',
                    value: 'phone_number',
                  },
                  {
                    label: 'Email ID',
                    value: 'email_ids',
                  },
                ]}
              />
            </div>
          </FormGroup>
        }
        modalActions={
          <ModalCTA
            eligibleCustomersDetails={eligibleCustomersDetails}
            handleSelectedData={handleSelectedData}
          />
        }
        hideCloseBtn
      />
    </div>
  );
};

export default UploadToUfh;
