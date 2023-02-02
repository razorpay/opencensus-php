import React, { useState } from 'react';
import { Formik } from 'formik';
import StepFooter from './StepFooter';
import { updatePartnerTypeAndConsent } from 'newAuth/signup/components/PartnerSignup/components/api';
import { SCREEN_NAME, STEPS, partnerTypeSelectionSchema } from 'newAuth/signup/Constants';
import { trackWithSegment } from 'newAuth/trackEvents';
import isEmpty from '@universe/utils/isEmpty';
import { StyledStepWrapper, StyledTitle, StyledSubtitle, StyledPartnerTypeTiles } from './styled';
import imageSelectReseller from 'assets/partner-dashboard/select-partner-type-reseller.svg';
import imageSelectAggregator from 'assets/partner-dashboard/select-partner-type-aggregator.svg';

const PartnerTypeSelection = ({ setStep, showNotification }) => {
  const [isLoading, setIsLoading] = useState(false);

  const onCTAClick = (partnerType) => {
    trackWithSegment({
      objectName: 'Partner Type Next',
      actionName: 'Clicked',
      location: SCREEN_NAME[STEPS.PARTNER_TYPE_SELECTION],
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
  const noop = () => {};
  return (
    <Formik initialValues={{}} validationSchema={partnerTypeSelectionSchema} onSubmit={noop}>
      {(formikProps) => (
        <form onChange={formikProps.handleChange}>
          <StyledStepWrapper>
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
                  e.stopPropagation();
                  formikProps.setFieldValue('partnerType', 'reseller');
                }}
              >
                <div className="pts-tile-content">
                  <div className="pts-image">
                    <img src={imageSelectReseller} alt="Select Partner Type Reseller" />
                  </div>
                  <div className="pts-description">
                    <span className="pts-heading">Reseller Partner</span>
                    <div className="pts-sub-heading">
                      Reseller Partners can refer their connections and get rewarded
                    </div>
                    <div class="desktop-only">
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
                  e.stopPropagation();
                  formikProps.setFieldValue('partnerType', 'aggregator');
                }}
              >
                <div className="pts-tile-content">
                  <div className="pts-image">
                    <img src={imageSelectAggregator} alt="Select Partner Type Aggregator" />
                  </div>
                  <div className="pts-description">
                    <span className="pts-heading">Aggregator Partner</span>
                    <div className="pts-sub-heading">
                      They Manage account and payment cycle for their merchants
                    </div>
                    <div class="desktop-only">
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
        </form>
      )}
    </Formik>
  );
};

export default PartnerTypeSelection;
