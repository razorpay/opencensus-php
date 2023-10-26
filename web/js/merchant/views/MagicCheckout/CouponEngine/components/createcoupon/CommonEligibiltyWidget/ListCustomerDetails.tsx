import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

// ui imports
import Input from 'common/new-ui/Input';
import Loader from 'common/ui/Loader';
import {
  FormGroup,
  ModalHeader,
  AddItemContainer,
  ModalCtaWrapper,
  HorizontalRadioButtons,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';

// helpers imports
import { showNotification } from 'merchant_common/reducers/notifications';

// api imports
import { getCustomerDetailsList } from 'merchant/views/MagicCheckout/CouponEngine/api';

interface ListCustomerDetailsProps {
  closeModal: () => void;
  setShouldShowUploadFileView: (value: boolean) => void;
  eligibleCustomersDetails: {
    type: string;
    id: string;
    name: string;
    source?: string;
  };
  setEligibleCustomersDetails: (details: any) => void;
  handleSelectedData: (data: any) => void;
  showNotification: (notification: any) => void;
}

interface CustomerData {
  id: string;
  name: string;
  source: string;
}

const ListCustomerDetails: React.FC<ListCustomerDetailsProps> = ({
  closeModal,
  setShouldShowUploadFileView,
  eligibleCustomersDetails,
  setEligibleCustomersDetails,
  handleSelectedData,
  showNotification,
}) => {
  const [customersData, setCustomersData] = useState<CustomerData[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [formattedDataForRadioButton, setFormattedDataForRadioButton] = useState<any[]>([]);

  useEffect(() => {
    const fetchCustomersData = async () => {
      try {
        setIsLoading(true);
        const res = await getCustomerDetailsList(eligibleCustomersDetails.type);
        const options = res.data.segments.map(({ name, id }) => ({
          label: name,
          value: id,
        }));
        setCustomersData(res.data.segments);
        setFormattedDataForRadioButton(options);
      } catch (err) {
        showNotification({
          type: 'error',
          message: 'Something went wrong while fetching customer details. Please try again later.',
        });
      } finally {
        setIsLoading(false);
      }
    };

    fetchCustomersData();
  }, [eligibleCustomersDetails.type]);

  const handleRadioButtonChange = (selectedCustomerDataId: string) => {
    const selectedCustomer = customersData.find(
      (customer) => customer.id === selectedCustomerDataId,
    );
    if (selectedCustomer) {
      setEligibleCustomersDetails({
        ...eligibleCustomersDetails,
        id: selectedCustomer.id,
        name: selectedCustomer.name,
        source: selectedCustomer.source,
      });
    }
  };

  const handleSubmit = () => {
    handleSelectedData(eligibleCustomersDetails);
    closeModal();
  };

  return (
    <div>
      <AddItemContainer>
        <ModalHeader>
          <div className="title">Add selected customers</div>
          <div className="exit-cta" onClick={closeModal}>
            <i className="i i-close" />
          </div>
        </ModalHeader>
        <FormGroup style={{ margin: '28px 0', gap: '30px' }}>
          <div className="form-label">Type</div>
          <div className="form-input">
            <Input.Radio
              autoRender
              name="couponEligibilityOn"
              defaultValue={eligibleCustomersDetails.type}
              onChange={(e) => {
                setEligibleCustomersDetails((prev) => ({
                  ...prev,
                  type: e.target.value,
                  id: '',
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
        <div>
          {isLoading ? (
            <Loader />
          ) : (
            <HorizontalRadioButtons>
              <div className="add-cta" onClick={() => setShouldShowUploadFileView(true)}>
                <i className="i i-plus" /> Upload{' '}
                {eligibleCustomersDetails.type === 'phone_number' ? 'Mobile Numbers' : 'Email IDs'}
              </div>
              <div className="form-input">
                <Input.Radio
                  autoRender
                  name="eligibleCustomersDetails"
                  defaultValue={eligibleCustomersDetails.id}
                  onChange={(e) => {
                    handleRadioButtonChange(e.target.value);
                  }}
                  options={formattedDataForRadioButton}
                />
              </div>
            </HorizontalRadioButtons>
          )}
        </div>
      </AddItemContainer>
      <ModalCtaWrapper>
        <div>{eligibleCustomersDetails.id ? 1 : 0} file uploaded</div>
        <div>
          <button className="secondary-cta" onClick={closeModal}>
            Cancel
          </button>
          <button className="primary-cta" onClick={handleSubmit}>
            Confirm
          </button>
        </div>
      </ModalCtaWrapper>
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ListCustomerDetails);
