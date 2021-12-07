import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { updateFeatures } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import SwitchField from 'common/ui/Forms/SwitchField';
import { getFeature } from 'common/utils/features';

function TokenisationConsent(props) {
  const [consentEnabled, enableConsent] = useState(false);

  useEffect(() => {
    if (props.features.length) {
      const consent = getTokenisationConsentFlag(props.features);
      enableConsent(consent);
    }
  }, [props.features.length]);

  function getTokenisationConsentFlag(features) {
    const consent = getFeature(features, 'disable_collect_consent');
    return !consent.value;
  }

  const toggleTokenisationConsent = (_consent, cb) => {
    const shouldSync = 0;
    const data = {
      features: {
        disable_collect_consent: consentEnabled,
      },
      should_sync: shouldSync,
    };

    return props
      .updateFeatures(data, props.user.current)
      .then(() => {
        cb(true);
        props.showNotification({
          type: 'success',
          message: 'Your preference was saved',
        });
        enableConsent((prevState) => !prevState);
      })
      .catch((err) => {
        cb(false);

        props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  const TOKENISATION_HEADING = 'Razorpay To Collect Consent For Saving Card Details';
  const TOKENISATION_DESCRIPTION = `As per RBI guidelines, it is mandatory to collect cardholder's consent before saving their card details. By switching this on, you are requesting Razorpay to collect cardholder's consent. You can turn this off if you are collecting cardholder's consent before saving card.`;

  return (
    <div class="panel panel-default">
      <div class="panel-heading">
        <span class="title">{TOKENISATION_HEADING}</span>

        <span class="toggler-btn">
          <SwitchField checked={consentEnabled} onChange={toggleTokenisationConsent} type="prime" />
          {consentEnabled ? (
            <b class="text-primary">Enabled</b>
          ) : (
            <b class="text-faded">Disabled</b>
          )}
        </span>
      </div>

      <div class="panel-body">
        <form class="form-horizontal">
          <div class="description">{TOKENISATION_DESCRIPTION}</div>
        </form>
      </div>
    </div>
  );
}
export default connect(
  (state) => {
    return {
      user: state.session.user,
      features: state.config.features,
    };
  },
  { updateFeatures, showNotification },
)(TokenisationConsent);
