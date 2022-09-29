import React, { useEffect, useState } from 'react';
import { ProductionAPIDetails } from './APIDetails';
import { ChooseAccount } from './ChooseAccount';
import { ChooseMode } from './ChooseMode';
import { onboardPaytmTerminal, getPaytmCredentials } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { connect } from 'react-redux';
import { ACCOUNT_LINKABLE } from 'merchant/views/Settings/PaymentMethods/constants';

const CHOOSE_ACCOUNT = 'choose-account';
const CHOOSE_MODE = 'choose-mode';
const PRODUCTION_API_DETAILS = 'production_api_details';

const RenderPage = ({ page, values, setValues, setDisabled }) => {
  switch (page) {
    case CHOOSE_MODE:
      return <ChooseMode setValues={(val) => setValues(val)} values={values} />;
    case CHOOSE_ACCOUNT:
      return <ChooseAccount setValues={(val) => setValues(val)} values={values} />;
    case PRODUCTION_API_DETAILS:
      return (
        <ProductionAPIDetails
          setValues={(val) => setValues(val)}
          values={values}
          setDisabled={(val) => setDisabled(val)}
        />
      );
    default:
      return null;
  }
};

const PaytmWalletIntegration = (props) => {
  const [page, setPage] = useState(CHOOSE_ACCOUNT);
  const [activeStep, setActiveStep] = useState(1);
  const [closeButton, setCloseButton] = useState(true);
  const [disabled, setDisabled] = useState(true);
  const [values, setValues] = useState({
    merchant_id: '',
    merchant_key: '',
    website_name: '',
    industry_type: '',
    has_account: true,
    mode: 'live',
  });

  const {
    closeModal,
    // eslint-disable-next-line no-shadow
    showNotification,
    fetchInstrumentStatus,
  } = props;

  function handleStepper(type) {
    if (closeButton) {
      return closeModal();
    }
    let currentStep = activeStep;
    if (type === 'increment') currentStep++;
    else currentStep--;
    return setActiveStep(currentStep);
  }

  const handleSubmit = () => {
    const { merchant_id, merchant_key, website_name, industry_type, mode } = values;

    return onboardPaytmTerminal(
      'paytm',
      merchant_key,
      merchant_id,
      industry_type,
      website_name,
      mode,
    )
      .then(() => {
        try {
          fetchInstrumentStatus();
          closeModal();
        } catch (err) {
          console.log('err', err);
          showNotification({
            type: 'error',
            message: err && err.errors[0],
          });
        }
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err && err.errors[0],
        });
      });
  };

  useEffect(() => {
    if (props.step) {
      setActiveStep(props.step);
      if (props.status !== ACCOUNT_LINKABLE) {
        getPaytmCredentials(props.user.id).then(({ data }) => {
          const fields = data[0];
          const identifiers = fields.identifiers;
          if (identifiers) {
            return setValues({
              ...values,
              merchant_id: identifiers.gateway_merchant_id,
              merchant_key: '',
              industry_type: identifiers.gateway_terminal_id,
              website_name: identifiers.gateway_access_code,
            });
          }
          return null;
        });
      }
    }
  }, []);

  useEffect(() => {
    switch (activeStep) {
      case 1:
        return setPage(CHOOSE_ACCOUNT);
      case 2:
        return setPage(PRODUCTION_API_DETAILS);
      default:
        return null;
    }
  }, [activeStep]);

  useEffect(() => {
    if (!values.has_account) {
      setCloseButton(true);
    } else {
      setCloseButton(false);
    }
  }, [values]);

  return (
    <>
      <div className="wizard-header">
        <p>Enable Paytm Wallet on Checkout </p>
        <div className="close" onClick={() => closeModal()}>
          <span />
          <span />
        </div>
      </div>
      <div className="paytm-wallet-integration">
        <div className="wizard">
          <RenderPage
            page={page}
            values={values}
            setValues={(val) => setValues(val)}
            setDisabled={(val) => setDisabled(val)}
          />
        </div>
        <div className="flex-end footer">
          <div className="action-buttons">
            {[2, 3].includes(activeStep) && props.step !== 2 && (
              <button className="btn btn-link" onClick={() => handleStepper('decrement')}>
                Previous
              </button>
            )}
            {[1].includes(activeStep) ? (
              <button className="btn btn-primary" onClick={() => handleStepper('increment')}>
                {closeButton ? 'Close' : 'Next'}
              </button>
            ) : (
              <button
                className="btn btn-primary"
                disabled={disabled}
                onClick={() => handleSubmit()}
              >
                {props.step === 2 ? 'Save Changes' : 'Submit'}
              </button>
            )}
          </div>
        </div>
      </div>
    </>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  showNotification,
})(PaytmWalletIntegration);
