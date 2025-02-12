import React, { useEffect } from 'react';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { DetailsDrawer } from './DetailsDrawer';

export const ProductionAPIDetails = ({ values, setValues, setDisabled }) => {
  function onChange(e) {
    return setValues({
      ...values,
      [e.target.name]: e.target.value,
    });
  }

  function validateMerchantId(val) {
    if (val.length < 20) {
      return 'Please add the valid merchant id';
    }
    return '';
  }
  function validateMerchantKey(val) {
    if (val.length < 16) {
      return 'Please add the valid merchant key';
    }
    return '';
  }

  function validateWebsite(val) {
    if (val.length < 1) {
      return 'Website is required';
    }
    return '';
  }

  useEffect(() => {
    const { merchant_id, merchant_key, website_name, industry_type } = values;

    const website = website_name.toLowerCase().split('.');

    if (
      merchant_id.length < 20 ||
      merchant_key.length < 16 ||
      website.includes('webstaging') ||
      !website_name.trim().length ||
      industry_type.length < 1
    ) {
      return setDisabled(true);
    } else {
      return setDisabled(false);
    }
  }, [values]);

  return (
    <>
      <Form>
        <main>
          <div className="form-container">
            <div className="form-title">Enter Production API Details</div>
            <p className="form-subtitle">
              Please make sure you{' '}
              <b>
                <i>enter the production API details</i>
              </b>{' '}
              only and{' '}
              <b>
                <i>NOT the Test Details</i>
              </b>
            </p>
            <div className="form-description">
              <div className="form-control-input">
                <label className="payment-method-label" />
                <Input
                  name="merchant_id"
                  label="Paytm Merchant ID"
                  placeholder="20 chars alpha-numeric ID"
                  value={values.merchant_id}
                  onChange={onChange}
                  validator={validateMerchantId}
                  maxLength="20"
                />
              </div>
              <div className="form-control-input">
                <label className="payment-method-label" />
                <Input
                  name="merchant_key"
                  label="Paytm Merchant Key"
                  placeholder="16 chars alpha-numeric Merchant Key"
                  value={values.merchant_key}
                  onChange={onChange}
                  validator={validateMerchantKey}
                  maxLength="16"
                />
              </div>
              <div className="form-control-input">
                <label className="payment-method-label" />
                <Input
                  name="website_name"
                  label="Paytm Website Name"
                  placeholder="DEFAULT"
                  value={values.website_name}
                  validator={validateWebsite}
                  onChange={onChange}
                />
              </div>
              <div className="form-control-input">
                <label className="payment-method-label" />
                <Input
                  name="industry_type"
                  label="Industry Type"
                  placeholder="Retail"
                  value={values.industry_type}
                  onChange={onChange}
                />
              </div>
            </div>
          </div>
        </main>
      </Form>
      <DetailsDrawer />
    </>
  );
};
