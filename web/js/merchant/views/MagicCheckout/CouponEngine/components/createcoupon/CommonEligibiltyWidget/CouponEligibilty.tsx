import React, { useContext, ChangeEvent } from 'react';
import isEmpty from 'lodash/isEmpty';
import { connect } from 'react-redux';
import { bindActionCreators, Dispatch } from 'redux';
import lazy from 'merchant/routes/LazyLoader';

// ui imports
import Input from 'common/new-ui/Input';
import { Accordion } from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/common/Accordian';
import {
  FormGroup,
  DottedButtonWrapper,
  DottedButton,
  GreyContainer,
  RemoveIcon,
} from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CreateCouponFormStyles';
import { ProductName } from 'merchant/views/MagicCheckout/CouponEngine/styles/AddProductModal';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

// context imports
import { ModalContext } from 'merchant/views/MagicCheckout/CouponEngine/context';

// helpers imports
import { openModal } from 'merchant_common/reducers/modals';
import { downloadFromUFH } from 'merchant/utils/downloadFile';
import { showNotification } from 'merchant_common/reducers/notifications';

const AddCustomerModal = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicCouponEngineAddCustomerModal' */ 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/CommonEligibiltyWidget/AddCustomerModal'
    ),
);

const EligibilityAccordionBody = ({ openModal, showNotification, flow }) => {
  const { widgetsData, setWidgetsData } = useContext(ModalContext);

  const handleSelectedData = (data: any) => {
    setWidgetsData((prev) => ({
      ...prev,
      couponEligibility: {
        ...prev.couponEligibility,
        customerList: [data.id],
        customerDetailsType: data.type,
        customerDisplayList: data,
      },
    }));
  };

  const handleCustomerGroupChange = (e: ChangeEvent<HTMLInputElement>) => {
    setWidgetsData((prev) => ({
      ...prev,
      couponEligibility: {
        ...prev.couponEligibility,
        customerGroup: e.target.value,
      },
    }));
  };

  const downloadCustomerDataAndNotify = async (id) => {
    try {
      await downloadFromUFH(id);
      showNotification({
        type: 'success',
        message: 'File downloaded successfully',
      });
    } catch (error) {
      showNotification({
        type: 'error',
        message: 'Something went wrong. Please try again later.',
      });
    }
  };

  return (
    <FormGroup>
      <div className="form-label">Apply Coupon For</div>
      <div className="form-input max-width-100">
        <Input.Radio
          key={widgetsData.couponEligibility.customerGroup}
          defaultValue={widgetsData.couponEligibility.customerGroup}
          onChange={handleCustomerGroupChange}
          autoRender
          name="couponEligibilityFor"
          options={[
            {
              label: 'All customers',
              value: 'allCustomers',
            },
            {
              label: 'Specific customers',
              value: 'specificCustomers',
            },
          ]}
          disabled={flow === 'edit' && widgetsData.status !== 'created'}
        />
        {widgetsData.couponEligibility.customerGroup === 'specificCustomers' && (
          <DottedButtonWrapper>
            {!isEmpty(widgetsData.couponEligibility.customerDisplayList) ? (
              <GreyContainer>
                <div className="display-flex gap--12 align-center">
                  <ProductName>
                    {widgetsData.couponEligibility.customerDisplayList.name}
                    <i
                      className="i i-download"
                      style={{ color: '#2a86f2', marginLeft: '8px', cursor: 'pointer' }}
                      onClick={() => {
                        downloadCustomerDataAndNotify(
                          widgetsData.couponEligibility.customerDisplayList.source as string,
                        );
                      }}
                    />
                  </ProductName>
                </div>
                {flow === 'edit' && widgetsData.status !== 'created' ? null : (
                  <div>
                    <span>
                      <RemoveIcon
                        className="i i-close"
                        onClick={() => {
                          setWidgetsData((prev) => ({
                            ...prev,
                            couponEligibility: {
                              ...prev.couponEligibility,
                              customerList: [],
                              customerDetailsType: '',
                              customerDisplayList: {},
                            },
                          }));
                        }}
                      />
                    </span>
                  </div>
                )}
              </GreyContainer>
            ) : (
              <DottedButton
                className="w-350"
                onClick={() =>
                  openModal({
                    size: 'large',
                    className: 'create-coupon-modal',
                    component: (
                      <SuspenseWithLoader type="center">
                        <AddCustomerModal handleSelectedData={handleSelectedData} />
                      </SuspenseWithLoader>
                    ),
                  })
                }
              >
                + Add Customers
              </DottedButton>
            )}
          </DottedButtonWrapper>
        )}
      </div>
    </FormGroup>
  );
};

const CouponEligibilityWidget = ({ openModal, showNotification, flow }) => {
  return (
    <div>
      <Accordion
        header={<div>Coupon eligibility</div>}
        body={
          <EligibilityAccordionBody
            openModal={openModal}
            showNotification={showNotification}
            flow={flow}
          />
        }
      />
    </div>
  );
};

const mapDispatchToProps = (dispatch: Dispatch) =>
  bindActionCreators(
    {
      openModal,
      showNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(CouponEligibilityWidget);
