//! This file is not used because Edit Payment Link feature is called off from Product
//! Slack Reference - https://razorpay.slack.com/archives/C043K5N223F/p1668411467771009?thread_ts=1668410847.606959&cid=C043K5N223F

import Loader from 'common/components/Loader';
import Input from 'common/new-ui/Input';
import { analyticsTrack } from 'common/utils/analytics';
import debounce from 'common/utils/debounce';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import isEmpty from 'lodash/isEmpty';
import {
  PAYMENT_HANDLE_DEFAULT_SUGGESTION_COUNT,
  PAYMENT_HANDLE_DOMAIN,
  PAYMENT_HANDLE_MAX_LENGTH,
  PAYMENT_HANDLE_MIN_LENGTH,
  PAYMENT_HANDLE_PREFIX,
  PAYMENT_HANDLE_REGEX,
  PAYMENT_HANDLE_SUGGESSTIONS_DEBOUNCE_TIME,
} from 'merchant/containers/Home/ProductOnboardingCard/constants';
import { merchantFetch } from 'merchant/utils/ajax';
import React, { useCallback, useEffect, useState } from 'react';

import ErrorIcon from 'assets/error-icon.svg';
import SuccessIcon from 'assets/success-tick-green.svg';

const getSlug = (url) => {
  const prefixIndex = url.indexOf(PAYMENT_HANDLE_PREFIX);
  return prefixIndex !== -1 ? url.slice(prefixIndex + 1) : '';
};

/**
 * @deprecated
 * @return {*} - Edit Payment Handle Input
 */
const EditPaymentHandle = ({
  paymentHandleData,
  isPaymentHandleEditable,
  updateIsHandleValid,
  updatePaymentHandleData,
  product,
}) => {
  const [paymentHandleUrl, setPaymentHandleUrl] = useState(
    paymentHandleData?.paymentHandleUrl ?? `${PAYMENT_HANDLE_DOMAIN}/${PAYMENT_HANDLE_PREFIX}`,
  );
  const [paymentHandleSlug, setPaymentHandleSlug] = useState(paymentHandleData?.paymentHandleSlug);
  const [handleSuggestions, setHandleSuggestions] = useState([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [showPaymentAvailability, setShowPaymentAvailability] = useState(false);
  const [errorMessage, setErrorMessage] = useState('');
  const [isPhAvailable, setIsPhAvailable] = useState(false);
  const [isFetchingAvailability, setIsFetchingAvailability] = useState(false);
  const [isFetchingSuggestions, setIsFetchingSuggestions] = useState(false);
  const [isDirty, setIsDirty] = useState(false);

  const isSlugChanged = paymentHandleData.paymentHandleSlug !== paymentHandleSlug;

  const hideAllHelpText = () => {
    setShowSuggestions(false);
    setShowPaymentAvailability(false);
    setErrorMessage('');
  };

  const showPaymentHandleInvalidError = (error) => {
    setShowSuggestions(false);
    setShowPaymentAvailability(false);
    setErrorMessage(error);
  };

  const getSuggestions = () => {
    if (!handleSuggestions.length && isPaymentHandleEditable) {
      setIsFetchingSuggestions(true);
      merchantFetch({
        url: `payment_handle/suggestion?count=${PAYMENT_HANDLE_DEFAULT_SUGGESTION_COUNT}`,
        mode: 'live',
      })
        .then(({ data }) => {
          setHandleSuggestions(data?.suggestions);
          setShowSuggestions(true);
        })
        .catch(() => {
          // do nothing
        })
        .finally(() => {
          setIsFetchingSuggestions(false);
        });
    }
  };

  const fetchPaymentAvailability = (slug = paymentHandleSlug) => {
    merchantFetch({
      url: `payment_handle/${slug}/exists`,
      mode: 'live',
    })
      .then(({ data }) => {
        if ('exists' in data) {
          const availability = !data.exists;
          setShowPaymentAvailability(true);
          setIsPhAvailable(availability);
        } else {
          hideAllHelpText();
        }
      })
      .catch(() => {
        hideAllHelpText();
      })
      .finally(() => {
        setIsFetchingAvailability(false);
      });
  };

  const onHandleChange = useCallback(
    debounce((text) => {
      hideAllHelpText();
      setIsFetchingAvailability(true);
      setShowSuggestions(false);

      if (isEmpty(text)) {
        setShowSuggestions(true);
        setIsFetchingAvailability(false);
        return;
      }

      if (text.length < PAYMENT_HANDLE_MIN_LENGTH) {
        showPaymentHandleInvalidError('Handle should have at least 4 characters');
        setIsFetchingAvailability(false);
        return;
      }

      if (!PAYMENT_HANDLE_REGEX.test(text)) {
        showPaymentHandleInvalidError('Only alphabets & numbers are allowed');
        setIsFetchingAvailability(false);
        return;
      }

      // fetch availability of payment handle
      if (text.length >= PAYMENT_HANDLE_MIN_LENGTH) {
        fetchPaymentAvailability(`${PAYMENT_HANDLE_PREFIX}${text}`);
      }
    }, PAYMENT_HANDLE_SUGGESSTIONS_DEBOUNCE_TIME),
    [],
  );

  const updatePaymentHandleUrl = (e) => {
    const slug = getSlug(e.target.value);
    if (
      e.target.value.includes(PAYMENT_HANDLE_PREFIX) &&
      slug.length <= PAYMENT_HANDLE_MAX_LENGTH
    ) {
      hideAllHelpText();
      setPaymentHandleSlug(`${PAYMENT_HANDLE_PREFIX}${slug}`);
      setPaymentHandleUrl(e.target.value);
      onHandleChange(slug);
      if (!isDirty) {
        analyticsTrack({
          objectName: 'PLO PH Edit',
          actionName: 'Initiated',
          screen: 'home page',
          properties: {
            product,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        setIsDirty(true);
      }
    }
  };

  const onSuggestedHandleClicked = (el) => {
    hideAllHelpText();
    setPaymentHandleSlug(el);
    setPaymentHandleUrl(`${PAYMENT_HANDLE_DOMAIN}/${el}`);
    setIsPhAvailable(true);
    setShowPaymentAvailability(true);
    analyticsTrack({
      objectName: 'PLO PH Suggestion',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        product,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  useEffect(() => {
    getSuggestions();
  }, []);

  useEffect(() => {
    if (paymentHandleData?.paymentHandleUrl) {
      setPaymentHandleUrl(paymentHandleData?.paymentHandleUrl);
    }
  }, [paymentHandleData]);

  useEffect(() => {
    const isHandleValid = paymentHandleSlug.length > 1 && isPhAvailable && !errorMessage;
    updateIsHandleValid(isHandleValid);
  }, [paymentHandleSlug, isPhAvailable, errorMessage]);

  useEffect(() => {
    updatePaymentHandleData({
      paymentHandleUrl,
      paymentHandleSlug,
    });
  }, [paymentHandleUrl, paymentHandleSlug]);

  return (
    <div>
      <Input
        value={paymentHandleUrl}
        name="payment_handle"
        disabled={!isPaymentHandleEditable}
        onChange={updatePaymentHandleUrl}
        type="text"
        title=""
        autoComplete="off"
        autoFocus
      />
      <div className="helper-text-container">
        {(isFetchingAvailability || isFetchingSuggestions) && (
          <div className="loader-container">
            <Loader width="15px" height="15px" />
            <span className="text">{isFetchingAvailability && 'Checking availability...'}</span>
          </div>
        )}
        {showSuggestions && handleSuggestions.length && (
          <div>
            {handleSuggestions.map((el) => {
              return (
                <React.Fragment key={el}>
                  <a className="suggested-handle" onClick={() => onSuggestedHandleClicked(el)}>
                    {el}
                  </a>{' '}
                </React.Fragment>
              );
            })}
            {handleSuggestions.length > 1 ? 'are available' : 'is available'}
          </div>
        )}
        {showPaymentAvailability &&
          isSlugChanged &&
          (isPhAvailable ? (
            <div className="handle-availability">
              <img height={12} src={SuccessIcon} alt="success-icon" />
              <a className="handle-name">{paymentHandleSlug}</a> is available
            </div>
          ) : (
            <div className="handle-availability">
              <img height={12} src={ErrorIcon} alt="success-icon" />
              <span className="handle-name">{paymentHandleSlug} </span>
              is not available
            </div>
          ))}
        {errorMessage && <div className="text-danger">{errorMessage}</div>}
      </div>
    </div>
  );
};

export default EditPaymentHandle;
