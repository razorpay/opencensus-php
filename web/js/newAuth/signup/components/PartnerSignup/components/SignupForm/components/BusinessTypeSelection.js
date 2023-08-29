import imageInfoIcon from 'assets/partner-dashboard/info-icon.png';
import { Modal, ModalBody } from 'common/components/Modal';
import Loader from 'common/ui/Loader';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import { Formik } from 'formik';
import isEmpty from 'lodash/isEmpty';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  updatePartnerTypeAndConsent,
  userPreSignup,
} from 'newAuth/signup/components/PartnerSignup/components/api';
import { businessTypeSelectionSchema, SCREEN_NAME, STEPS } from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';
import React, { useEffect, useState } from 'react';
import BusinessTypeInfo from './BusinessTypeInfo';
import StepFooter from './StepFooter';
import {
  StyledBtypeLabel,
  StyledForm,
  StyledInfoIcon,
  StyledStepWrapper,
  StyledSubtitle,
  StyledTileHeading,
  StyledTilesAll,
  StyledTileWrap,
  StyledTitle,
} from './styled';

const BusinessTypeSelection = ({
  setStep,
  closeModal,
  showNotification,
  contactName,
  onboardAllAsResellerFlag,
}) => {
  const [registered, setRegistered] = useState(null);
  const [unregistered, setUnregistered] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [businessInfoData, setBusinessInfoData] = useState(false);

  useEffect(() => {
    trackWithSegment({
      objectName: 'Business Type Screen',
      actionName: 'Displayed',
      location: SCREEN_NAME[STEPS.BUSINESS_TYPE_SELECTION],
    });
    setIsLoading(true);
    merchantFetch('merchant/onboarding/business_types')
      .then((res) => {
        if (res.status_code === 200 && res.success) {
          setRegistered(res?.data?.registered);
          setUnregistered(res?.data?.unregistered);
        }
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, []);

  const onInfoIconClick = (label) => {
    trackWithSegment({
      objectName: 'Business Type Help Screen',
      actionName: 'Clicked',
      location: SCREEN_NAME[STEPS.BUSINESS_TYPE_SELECTION],
    });
    setBusinessInfoData({ isOpen: true, label });
  };
  const onModalClose = () => {
    setBusinessInfoData((data) => ({ ...data, isOpen: false }));
    closeModal();
  };

  const onCTAClick = (businessType) => {
    trackWithSegment({
      objectName: 'Business Type Next',
      actionName: 'Clicked',
      location: SCREEN_NAME[STEPS.BUSINESS_TYPE_SELECTION],
    });
    setIsLoading(true);
    return userPreSignup({
      business_type: businessType,
      contact_name: contactName,
    })
      .then(({ success }) => {
        if (success) {
          if (onboardAllAsResellerFlag) {
            updatePartnerTypeAndConsent('reseller')
              .then(() => {
                setIsLoading(false);
                // partner type selection step will be skipped in onboarding all as resellers
                setStep((step) => step + 2);
              })
              .catch((err) => {
                setIsLoading(false);
                showNotification({
                  type: 'error',
                  message: err.errors?.[0] || 'Please try again',
                });
              });
          } else {
            setIsLoading(false);
            setStep((step) => step + 1);
          }
        }
      })
      .catch((err) => {
        setIsLoading(false);

        showNotification({
          type: 'error',
          message: err.errors?.[0] || 'Please try again',
        });
      });
  };

  const onBusinessTypeSelect = (formikProps, businessType) => {
    if (!formikProps.touched.businessType) {
      trackWithSegment({
        objectName: 'Business Type',
        actionName: 'Initiated',
        location: SCREEN_NAME[STEPS.BUSINESS_TYPE_SELECTION],
      });
    }
    formikProps.setFieldTouched('businessType');
    formikProps.setFieldValue('businessType', businessType?.id);
  };

  const noop = () => {};

  return (
    <Formik initialValues={{}} validationSchema={businessTypeSelectionSchema} onSubmit={noop}>
      {(formikProps) => (
        <StyledForm onChange={formikProps.handleChange}>
          <StyledStepWrapper>
            <StyledTitle onClick={closeModal}>Select Business Type</StyledTitle>
            <StyledSubtitle>Pick only one that applies to your business</StyledSubtitle>
            {!unregistered || !registered ? (
              <Loader />
            ) : (
              <StyledTileWrap>
                <StyledTileHeading>
                  <span>NOT REGISTERED</span>
                  <StyledInfoIcon
                    title="Click for more info"
                    onClick={() => {
                      onInfoIconClick('unreg');
                    }}
                  >
                    <img src={imageInfoIcon} alt="info" />
                  </StyledInfoIcon>
                </StyledTileHeading>
                <StyledTilesAll>
                  {unregistered?.map((businessType) => (
                    <StyledBtypeLabel
                      key={businessType?.id}
                      $isActive={businessType?.id === formikProps.values.businessType}
                      onClick={() => onBusinessTypeSelect(formikProps, businessType)}
                    >
                      {businessType?.label}
                    </StyledBtypeLabel>
                  ))}
                </StyledTilesAll>

                <StyledTileHeading>
                  <span>REGISTERED</span>
                  <StyledInfoIcon
                    title="Click for more info"
                    onClick={() => {
                      onInfoIconClick('reg');
                    }}
                  >
                    <img src={imageInfoIcon} alt="info" />
                  </StyledInfoIcon>
                </StyledTileHeading>
                <StyledTilesAll>
                  {registered?.map((businessType) => (
                    <StyledBtypeLabel
                      key={`reg-${businessType?.id}`}
                      $isActive={businessType?.id === formikProps.values.businessType}
                      onClick={() => onBusinessTypeSelect(formikProps, businessType)}
                    >
                      {businessType?.label}
                    </StyledBtypeLabel>
                  ))}
                </StyledTilesAll>
              </StyledTileWrap>
            )}
          </StyledStepWrapper>
          <StepFooter
            ctaText="Next"
            onClick={() => onCTAClick(formikProps.values.businessType)}
            disabled={isLoading || !isEmpty(formikProps.errors) || isEmpty(formikProps.touched)}
            isLoading={isLoading}
          />

          <Modal
            bottomsheet={isMobileAndTablet()}
            bottomSheetHeight="430px"
            isOpen={businessInfoData.isOpen}
            onClose={onModalClose}
          >
            <ModalBody>
              <BusinessTypeInfo label={businessInfoData.label} />
            </ModalBody>
          </Modal>
        </StyledForm>
      )}
    </Formik>
  );
};

export default BusinessTypeSelection;
