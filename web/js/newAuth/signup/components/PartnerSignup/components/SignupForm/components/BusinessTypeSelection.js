import React, { useState, useEffect } from 'react';
import { Formik } from 'formik';
import StepFooter from './StepFooter';
import { userPreSignup } from 'newAuth/signup/components/PartnerSignup/components/api';
import { businessTypeSelectionSchema } from 'newAuth/signup/Constants';
import imageInfoIcon from 'assets/partner-dashboard/info-icon.png';
import { merchantFetch } from 'merchant/utils/ajax';
import BusinessTypeInfo from './BusinessTypeInfo';
import Loader from 'common/ui/Loader';
import { trackWithSegment } from 'newAuth/trackEvents';
import isEmpty from '@universe/utils/isEmpty';
import { isMobileAndTablet } from 'common/utils/rzp-utils';
import BottomSheet from 'common/components/BottomSheet';
import {
  StyledStepWrapper,
  StyledTitle,
  StyledSubtitle,
  StyledTileWrap,
  StyledTileHeading,
  StyledTilesAll,
  StyledBtypeLabel,
  StyledInfoIcon,
} from './styled';

const BusinessTypeSelection = ({
  setStep,
  openModal,
  closeModal,
  showNotification,
  contactName,
}) => {
  const [registered, setRegistered] = useState(null);
  const [unregistered, setUnregistered] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [isBottomSheetOpen, setIsBottomSheetOpen] = useState(false);

  useEffect(() => {
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
    if (isMobileAndTablet()) setIsBottomSheetOpen(true);
    else
      openModal({
        size: 'signup-info',
        component: <BusinessTypeInfo label={label} closeModal={closeModal} />,
      });
  };

  const onCTAClick = (businessType) => {
    trackWithSegment({
      objectName: 'Get Started',
      actionName: 'Clicked',
      location: 'Mobile Number',
    });
    setIsLoading(true);
    return userPreSignup({
      business_type: businessType,
      contact_name: contactName,
    })
      .then(({ success }) => {
        if (success) {
          setStep((step) => step + 1);
        }
        setIsLoading(false);
      })
      .catch((err) => {
        setIsLoading(false);

        showNotification({
          type: 'error',
          message: err.errors?.[0] || 'Please try again',
        });
      });
  };

  const noop = () => {};

  return (
    <Formik initialValues={{}} validationSchema={businessTypeSelectionSchema} onSubmit={noop}>
      {(formikProps) => (
        <form onChange={formikProps.handleChange}>
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
                  {unregistered?.map((btype) => (
                    <StyledBtypeLabel
                      key={btype?.id}
                      $isActive={btype?.id === formikProps.values.businessType}
                      onClick={() => {
                        formikProps.setFieldTouched('businessType');
                        formikProps.setFieldValue('businessType', btype?.id);
                      }}
                    >
                      {btype?.label}
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
                  {registered?.map((btype) => (
                    <StyledBtypeLabel
                      key={`reg-${btype?.id}`}
                      $isActive={btype?.id === formikProps.values.businessType}
                      onClick={() => {
                        formikProps.setFieldTouched('businessType');
                        formikProps.setFieldValue('businessType', btype?.id);
                      }}
                    >
                      {btype?.label}
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
          <BottomSheet isOpen={isBottomSheetOpen}>
            <BusinessTypeInfo closeModal={closeModal} />
          </BottomSheet>
        </form>
      )}
    </Formik>
  );
};

export default BusinessTypeSelection;
