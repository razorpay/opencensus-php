import imageSelectAggregator from 'assets/partner-dashboard/select-partner-type-aggregator.svg';
import imageSelectReseller from 'assets/partner-dashboard/select-partner-type-reseller.svg';
import { Formik } from 'formik';
import isEmpty from 'lodash/isEmpty';
import { updatePartnerTypeAndConsent } from 'newAuth/signup/components/PartnerSignup/components/api';
import { partnerTypeSelectionSchema, SCREEN_NAME, STEPS } from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';
import React, { useEffect, useState } from 'react';
import StepFooter from './StepFooter';
import {
  StyledForm,
  StyledPartnerTypeTiles,
  StyledStepWrapper,
  StyledSubtitle,
  StyledTitle,
} from './styled';

const PartnerTypeSelection = ({ setStep, showNotification, onboardAllAsResellerFlag }) => {
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    trackWithSegment({
      objectName: 'Partner Type Screen',
      actionName: 'Displayed',
      location: SCREEN_NAME[STEPS.PARTNER_TYPE_SELECTION],
      properties: {
        onboardAllAsResellerFlag,
      },
    });
  }, []);

  const onCTAClick = (partnerType) => {
    trackWithSegment({
      objectName: 'Partner Type Next',
      actionName: 'Clicked',
      location: SCREEN_NAME[STEPS.PARTNER_TYPE_SELECTION],
      properties: {
        partnerTypeSelected: partnerType,
        onboardAllAsResellerFlag,
      },
    });
    setIsLoading(true);
    return updatePartnerTypeAndConsent(partnerType)
      .then(() => {
        setIsLoading(false);
        setStep((step) => step + 1);
      })
      .catch((err) => {
        setIsLoading(false);
        showNotification({
          type: 'error',
          message: err.errors?.[0] || 'Please try again',
        });
      });
  };

  const onPartnerTypeSelect = (e, formikProps, fieldValue) => {
    e.stopPropagation();
    formikProps.setFieldValue('partnerType', fieldValue);

    if (!formikProps.touched.partnerType)
      trackWithSegment({
        objectName: 'Partner Type',
        actionName: 'Initiated',
        location: SCREEN_NAME[STEPS.PARTNER_TYPE_SELECTION],
        properties: {
          onboardAllAsResellerFlag,
          partnerType: fieldValue,
        },
      });
  };

  const noop = () => {};
  return (
    <Formik initialValues={{}} validationSchema={partnerTypeSelectionSchema} onSubmit={noop}>
      {(formikProps) => (
        <StyledForm onSubmit={(e) => e.preventDefault()} onChange={formikProps.handleChange}>
          <StyledStepWrapper $mobileOverflow="unset">
            <StyledTitle>Choose your Partner Type</StyledTitle>
            <StyledSubtitle $textAlign="center">
              Pick only one that applies to your business
            </StyledSubtitle>
            <StyledPartnerTypeTiles>
              <div
                className={`pts-tile-wrap ${
                  formikProps.values.partnerType === 'reseller' ? 'active' : ''
                }`}
                onClick={(e) => {
                  onPartnerTypeSelect(e, formikProps, 'reseller');
                }}
              >
                <div className="pts-tile-content">
                  <img src={imageSelectReseller} alt="Select Partner Type Reseller" />
                  <div className="pts-description">
                    <span className="pts-heading">Reseller Partner</span>
                    <div className="pts-sub-heading">
                      Reseller Partners can refer their connections and get rewarded
                    </div>
                    <div className="desktop-only">
                      <div className="row pts-sub-description">
                        <div className="column">
                          <ul>
                            <li>No cap on earnings</li>
                          </ul>
                        </div>
                        <div className="column">
                          <ul>
                            <li>Automated commissions</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div
                className={`pts-tile-wrap ${
                  formikProps.values.partnerType === 'aggregator' ? 'active' : ''
                }`}
                onClick={(e) => {
                  onPartnerTypeSelect(e, formikProps, 'aggregator');
                }}
              >
                <div className="pts-tile-content">
                  <img src={imageSelectAggregator} alt="Select Partner Type Aggregator" />
                  <div className="pts-description">
                    <span className="pts-heading">Aggregator Partner</span>
                    <div className="pts-sub-heading">
                      They Manage account and payment cycle for their merchants
                    </div>
                    <div className="desktop-only">
                      <div className="row pts-sub-description">
                        <div className="column">
                          <ul>
                            <li>{'Manage Merchant Account'} </li>
                            <li>{'Automated commissions'}</li>
                          </ul>
                        </div>
                        <div className="column">
                          <ul>
                            <li>{'No cap on earnings'}</li>
                            <li>
                              Requires ({/**/}
                              <a
                                href="https://razorpay.com/docs/partners/aggregators/partner-auth"
                                target="_blank"
                                rel="noreferrer noopener"
                                className="blue-link"
                              >
                                Partner Auth
                              </a>
                              {/**/}) Integration
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="error-message">{formikProps.errors.partnerType}</div>
            </StyledPartnerTypeTiles>
          </StyledStepWrapper>
          <StepFooter
            ctaText="Next"
            onClick={() => onCTAClick(formikProps.values.partnerType)}
            isLoading={isLoading}
            disabled={!isEmpty(formikProps.errors) || isEmpty(formikProps.values.partnerType)}
          />
        </StyledForm>
      )}
    </Formik>
  );
};

export default PartnerTypeSelection;
