import React, { useEffect, useState } from 'react';
import { AggregatorFormT, AggregatorFormErrorT } from '../../TypesDeclare/home';
import { compose } from 'redux';
import { reduxForm } from 'redux-form';
import rTracking from 'react-tracking';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';
import Radio from '@razorpay/blade-old/src/atoms/Radio';
import ScrollView from '@razorpay/blade-old/src/atoms/ScrollView';
import Button from 'common/new-ui/Button';
import { isUrlLenient, isMobile } from 'common/utils/validators';

const AggregatorForm = ({
  closeModal,
  isMobileAndTablet,
  handleSubmitAggregator,
  contactNumber,
  mid,
  tracking,
}: AggregatorFormT): JSX.Element => {
  const [phoneNumber, setPhoneNumber] = useState<number | null>(null);
  const [reason, setReason] = useState('');
  const [isHandleRisk, setIsHandleRisk] = useState('Yes');
  const [websiteURL, setWebsiteURL] = useState('');
  const [businessType, setBusinessType] = useState('ERP');
  const [otherBusinessType, setOtherBusinessType] = useState('');
  const [error, setError] = useState<AggregatorFormErrorT>({
    isError: false,
    phoneNumber: '',
    reason: '',
    websiteURL: '',
    otherBusinessType: '',
  });

  useEffect(() => {
    setPhoneNumber(contactNumber);
  }, [contactNumber]);

  const trackFormChange = (label) => {
    tracking?.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_aggr_req.fill_form.initiated', {
        device: isMobileAndTablet ? 'mobile' : 'desktop',
        mid,
        label,
      }),
    );
  };

  const onFormBlur = (label) => {
    const err: {
      isError: boolean;
      phoneNumber: string;
      reason: string;
      websiteURL: string;
      otherBusinessType: string;
    } = {
      isError: false,
      phoneNumber: '',
      reason: '',
      websiteURL: '',
      otherBusinessType: '',
    };
    err.isError = false;
    const isCheckAll = label === 'all';
    if (label === 'phoneNumber' || isCheckAll) {
      if (!phoneNumber) {
        err.isError = true;
        err.phoneNumber = 'Required Field';
      } else if (!isMobile(phoneNumber)) {
        err.isError = true;
        err.phoneNumber = 'Enter a valid mobile number';
      }
    }

    if (label === 'reason' || isCheckAll) {
      if (!(reason && reason.length > 0)) {
        err.isError = true;
        err.reason = 'Required Field';
      }
    }

    if (label === 'websiteURL' || isCheckAll) {
      if (!(websiteURL && websiteURL.length > 0)) {
        err.isError = true;
        err.websiteURL = 'Required Field';
      } else if (!isUrlLenient(websiteURL)) {
        err.isError = true;
        err.websiteURL = 'Enter a valid URL';
      }
    }

    if (label === 'otherBusinessType' || isCheckAll) {
      if (businessType.toLowerCase() === 'others' && otherBusinessType.length <= 0) {
        err.isError = true;
        err.otherBusinessType = 'Required Field';
      }
    }

    setError(err);
    return err.isError;
  };

  const onSubmit = () => {
    tracking?.trackEvent(
      window.rzpQ.onbr().interaction('partnerships.partner_aggr_req.submit_form', {
        device: isMobileAndTablet ? 'mobile' : 'desktop',
        mid,
        phoneNumber,
        reason,
        isHandleRisk,
        websiteURL,
        businessType,
        otherBusinessType,
      }),
    );
    if (!onFormBlur('all')) {
      handleSubmitAggregator(
        phoneNumber,
        reason,
        isHandleRisk,
        websiteURL,
        businessType,
        otherBusinessType,
      );
    }
  };

  return (
    <ScrollView>
      <div className="agg-container">
        <div className="modal-left-side">
          <button
            type="button"
            className="close agg-close"
            onClick={() => {
              tracking?.trackEvent(
                window.rzpQ.onbr().interaction('partnerships.partner_aggr_req.fill_form.cancel', {
                  device: isMobileAndTablet ? 'mobile' : 'desktop',
                  mid,
                }),
              );
              closeModal();
            }}
          >
            <i className="i i-close" />
          </button>
          <div className="left-content">
            <div className="agg-content-header">
              {isMobileAndTablet ? (
                <>
                  <div>Manage account</div>
                  <div>of your sub-merchants</div>
                </>
              ) : (
                'Manage sub-merchant Accounts'
              )}
            </div>
            <hr className="seperator-line" />
            <ul className="agg-content-list">
              <li>Manage Merchant Account</li>
              <li>Get Referral Bonus</li>
              <li>Automated Commisions</li>
              <li>
                Requires &nbsp;
                <a
                  href="https://razorpay.com/docs/partners/aggregators/partner-auth/"
                  target="__blank"
                >
                  (Partner Auth)
                </a>{' '}
                Integration
              </li>
            </ul>
          </div>
        </div>
        <div className="modal-right-side">
          <div className="right-header">
            <button
              type="button"
              className="close agg-close"
              onClick={() => {
                tracking?.trackEvent(
                  window.rzpQ.onbr().interaction('partnerships.partner_aggr_req.fill_form.cancel', {
                    device: isMobileAndTablet ? 'mobile' : 'desktop',
                    mid,
                  }),
                );
                closeModal();
              }}
            >
              <i className="i i-close" />
            </button>
          </div>
          <div className="form-wrap">
            <div className="form-group mb-20">
              <TextInput
                width="auto"
                label="Phone Number"
                className="form-control"
                autoFocus
                placeholder="Enter phone number"
                variant="filled"
                type="number"
                maxLength={10}
                value={phoneNumber}
                onChange={(val) => {
                  trackFormChange('Phone Number');
                  setPhoneNumber(val);
                }}
                onBlur={() => {
                  onFormBlur('phoneNumber');
                }}
                errorText={error.phoneNumber}
              />
            </div>

            <div className="form-group mb-20">
              <TextInput
                width="auto"
                label="Why do you need to manage the transactions of your affiliates?"
                className="form-control"
                variant="filled"
                placeholder="Type your response here"
                value={reason}
                maxLength={500}
                onChange={(val) => {
                  trackFormChange('Reason');
                  setReason(val);
                }}
                onBlur={() => {
                  onFormBlur('reason');
                }}
                errorText={error.reason}
              />
            </div>

            <div className="form-group mb-20">
              <div className="light-label dark-label mb-6">
                Would you also handle the Risk/ Compliance for your affiliate's Razorpay account?
              </div>
              <Radio
                defaultValue={isHandleRisk}
                onChange={(val) => {
                  trackFormChange('Will Handle Risk');
                  setIsHandleRisk(val);
                }}
              >
                <Radio.Option value="Yes" title="Yes" name="risk" />
                <Radio.Option value="No" title="No" name="risk" />
              </Radio>
            </div>

            <div className="form-group mb-20">
              <TextInput
                width="auto"
                label="Website URL"
                className="form-control"
                variant="filled"
                placeholder="Enter URL"
                value={websiteURL}
                onChange={(val) => {
                  trackFormChange('Website URL');
                  setWebsiteURL(val);
                }}
                onBlur={() => {
                  onFormBlur('websiteURL');
                }}
                errorText={error.websiteURL}
              />
            </div>

            <div className="form-group mb-20">
              <div className="light-label dark-label mb-8">Business Type</div>
              <select
                name="business_type"
                ng-model="data.business_type"
                className="form-control"
                id="filled-input-bkg"
                required
                ng-disabled="locked"
                onChange={(event) => {
                  trackFormChange('Business Type');
                  setBusinessType(event.target.value);
                }}
              >
                <option value="ERP">ERP</option>
                <option value="Marketplace">Marketplace</option>
                <option value="Others">Others</option>
              </select>
            </div>

            {businessType === 'Others' && (
              <div className="form-group mb-20">
                <TextInput
                  width="auto"
                  label="If others, Please specify!"
                  className="form-control"
                  variant="filled"
                  placeholder="Type your response"
                  value={otherBusinessType}
                  maxLength={500}
                  onChange={(val) => {
                    trackFormChange('Other Business Type');
                    setOtherBusinessType(val);
                  }}
                  onBlur={() => {
                    onFormBlur('otherBusinessType');
                  }}
                  errorText={error.otherBusinessType}
                />
              </div>
            )}

            <div className="mt-24 mb-40">
              <Button.Primary onClick={onSubmit} className={`${isMobileAndTablet && 'full-width'}`}>
                <span className={`${isMobileAndTablet ? 'device--mobile' : 'device--desktop'}`}>
                  Submit
                </span>
              </Button.Primary>
            </div>
          </div>
        </div>
      </div>
    </ScrollView>
  );
};

export default compose<any>(
  rTracking(() => window.rzpQ.component('AggregatorForm')),
  reduxForm({
    form: 'aggregatorForm',
  }),
)(AggregatorForm);
