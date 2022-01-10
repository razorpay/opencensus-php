import React, { useState, useEffect } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import InputField from 'common/ui/Forms/InputField';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { fetchCreditBalance as fetchCreditBalanceReducer } from 'merchant/reducers/credits';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { Field, reduxForm } from 'redux-form';
import { required } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';
import { getFormattedAmount, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';

const ApplyCouponCodeModal = ({
  onComplete,
  onClose,
  fetchCreditBalance,
  showNotification,
  valid,
  handleSubmit,
}) => {
  const [coupon, setCoupon] = useState(null);
  const [token, setToken] = useState(null);
  const [credits, setCredits] = useState(null);
  const [error, setError] = useState(null);

  const onCouponChange = (value) => {
    setError(null);
    setCoupon(value);
  };

  const onSubmit = () => {
    analyticsTrack({
      objectName: `coupon code ${token ? 'confirm' : 'submit'}`,
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        couponCode: coupon,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    const data = {
      code: coupon,
    };

    if (token) data.token = token;

    return merchantFetch({
      url: 'merchant/activation/coupons/apply',
      method: 'POST',
      data,
    })
      .then((res) => {
        if (res && res.data) {
          analyticsTrack({
            objectName: `coupon code ${token ? 'confirm' : 'submit'}`,
            actionName: 'result',
            screen: 'my account',
            properties: {
              couponCode: coupon,
              applied: res.data.applied,
              result: 'Success',
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
          if (!res.data.applied) {
            setToken(res.data.token);
            setCredits(res.data.data.available_credits);
          } else {
            fetchCreditBalance();
            showNotification({
              type: 'success',
              message: 'Coupon code applied successfully.',
            });
            onComplete?.();
          }
        }
      })
      .catch((err) => {
        analyticsTrack({
          objectName: `coupon code ${token ? 'confirm' : 'submit'}`,
          actionName: 'result',
          screen: 'my account',
          properties: {
            couponCode: coupon,
            result: 'Failure',
            failureMessage: err.errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        if (!token) {
          setError(err.errors[0]);
        } else {
          showNotification({
            type: 'error',
            message: err.errors,
          });
        }
      });
  };
  const _onClose = () => {
    onClose?.();
  };

  useEffect(() => {
    analyticsTrack({
      objectName: 'coupon code popup',
      actionName: 'displayed',
      screen: 'my account',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, []);

  return (
    <div class="coupon-code-modal">
      <ModalHeader title="Apply Coupon Code" onCloseClick={_onClose} />
      {!token ? (
        <div class="modal-body">
          <form onSubmit={handleSubmit(onSubmit)}>
            <div class="form-group">
              <label className="control-label" htmlFor="coupon">
                Coupon Code
              </label>
              <Field
                component={InputField}
                type="text"
                name="coupon"
                class="form-control"
                onChange={(_, value) => onCouponChange(value)}
                validate={required()}
                autoFocus={true}
              />
              <small class="text-danger">{error ? error : '\u00A0'}</small>
            </div>
            <AsyncButton
              class="btn btn-primary btn-block"
              text="Apply"
              type="submit"
              pendingText="Applying..."
              disabled={!valid}
              onClick={handleSubmit(onSubmit)}
            />
          </form>
        </div>
      ) : (
        <div className="modal-body">
          <div className="merchant-note">
            You already have amount credits worth <b>₹{getFormattedAmount(credits)}</b> active. If
            you apply this coupon code, all the existing amount credits will expire. Do you want to
            continue?
          </div>
          <div className="modal-actions">
            <button type="button" className="btn btn-outline" onClick={_onClose}>
              Cancel
            </button>
            <AsyncButton
              class="btn btn-primary"
              text="Apply Coupon Code"
              pendingText="Applying..."
              disabled={error}
              onClick={handleSubmit(onSubmit)}
            />
          </div>
        </div>
      )}
    </div>
  );
};

export default compose(
  connect(null, {
    fetchCreditBalance: fetchCreditBalanceReducer,
    showNotification: fnShowNotification,
  }),
  reduxForm({
    form: 'ApplyCouponCodeForm',
  }),
)(ApplyCouponCodeModal);
