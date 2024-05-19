import { useState, useEffect } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import PrepayCODToggle from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/PrepayCODToggle';
import Discount from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/Discount';
import LinkValidity from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/LinkValidity';
import ConversionPlatform from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/ConversionPlatform';
import ConvertCategoryField from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/components/ConvertCategoryField';
import CredentialsModal from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce';

import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { updatePrepayCODConfigs } from 'merchant/reducers/magicCheckout/prepayCOD/configDashboard/action';

import {
  getConvertRiskCategoryArray,
  isValidDuration,
  isDiscountInvalid,
  isDurationInvalid,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/utils';

import { analyticsTrack } from 'common/utils/analytics';
import { toHoursAndMinutes, getTimeInSeconds } from 'merchant/views/MagicCheckout/helper';

import {
  NOTIFICATION_MSGS,
  VALIDATION_MSGS,
  SAVE_CONFIGS_CONFIRMATION_TEXTS,
  MAX_MINS,
  MAX_HOURS,
  MIN_TIME,
  DISCOUNT_TYPE,
  CREDENTIALS_MODAL_DESC,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';

const CODPrepaidConfigs = (props) => {
  const {
    setIsConfigSaved,
    openModal,
    closeModal,
    showPrepayCODToggle,
    isPrepayCODEnabled: isPrepayCODOpted,
    prepayCODConfigs,
    updateConfigs,
    showNotification,
    isManualReviewOpted,
    platform,
    shopId: shop_id,
    user,
  } = props;

  const {
    discount: prepayDiscount,
    communication: { methods = [], expire_seconds = '' } = {},
    risk_category = [],
  } = prepayCODConfigs;

  const [convertRiskCategory, setConvertRiskCategory] = useState('');
  const [availDiscount, setAvailDiscount] = useState(prepayDiscount?.type !== DISCOUNT_TYPE.zero);
  const [discountType, setDiscountType] = useState(prepayDiscount?.type || DISCOUNT_TYPE.flat);
  const [discount, setDiscount] = useState({
    percent: '',
    maxDiscount: '',
    minOrderValue: '',
    error: null,
  });
  const [validityType, setValidityType] = useState('');
  const [convertOn, setConvertOn] = useState('whatsapp');
  const [isPrepayCODEnabled, setIsPrepayCODEnabled] = useState(isPrepayCODOpted);
  const [durationVal, setDurationVal] = useState({
    hours: 0,
    mins: 0,
    error: { hours: null, mins: null },
  });
  const [isCtaDisabled, setIsCtaDisabled] = useState(false);

  useEffect(() => {
    const isDisabled =
      (isManualReviewOpted && !convertRiskCategory) ||
      !validityType ||
      (availDiscount && isDiscountInvalid(discount, discountType)) ||
      isDurationInvalid(validityType, durationVal);

    setIsCtaDisabled(isDisabled);
  }, [
    convertRiskCategory,
    availDiscount,
    discount,
    discountType,
    validityType,
    convertOn,
    durationVal,
  ]);

  useEffect(() => {
    if (risk_category.length === 0) return;

    if (risk_category.length === 3) {
      setConvertRiskCategory('all');
    } else if (risk_category.length === 2) {
      setConvertRiskCategory('highMedium');
    } else {
      setConvertRiskCategory(risk_category[0] ?? '');
    }
  }, [risk_category]);

  useEffect(() => {
    if (expire_seconds === '') return;

    if (expire_seconds == 900 || expire_seconds == 1800) {
      setValidityType(expire_seconds);
    } else {
      setValidityType('custom');
      const res = toHoursAndMinutes(expire_seconds);
      setDurationVal({ hours: res.hours, mins: res.mins, error: { hour: null, mins: null } });
    }
  }, [expire_seconds]);

  useEffect(() => {
    const {
      type = '',
      discount_percentage = 0,
      max_discount = 0,
      minimum_order_value = 0,
    } = prepayDiscount || {};

    if (type === DISCOUNT_TYPE.flat) {
      setDiscount({
        percent: '',
        maxDiscount: max_discount / 100,
        minOrderValue: minimum_order_value / 100,
        error: null,
      });
    } else if (type === DISCOUNT_TYPE.percentage) {
      setDiscount({
        percent: discount_percentage,
        maxDiscount: max_discount !== 0 ? max_discount / 100 : '',
        minOrderValue: minimum_order_value / 100,
        error: null,
      });
    }
  }, [prepayDiscount]);

  useEffect(() => {
    if (!Object.entries(prepayCODConfigs).length) setIsPrepayCODEnabled(true);
  }, [prepayCODConfigs]);

  useEffect(() => {
    if (!methods.length) {
      return;
    }

    if (methods.length === 2) {
      setConvertOn('both');
    } else {
      setConvertOn(methods[0]);
    }
  }, [methods]);

  const showAlertNotification = () => {
    showNotification({
      type: 'neutral',
      message: NOTIFICATION_MSGS.credentialsModalClose,
      closeTimeout: 10000,
      className: 'magic-notification',
    });
    closeModal();
  };

  const addConfigs = (params = {}) => {
    analyticsTrack({
      objectName: `1ccMdClickedSavePrepayConfigs`,
      actionName: 'clicked',
      screen: `confirmation modal l1`,
      properties: {
        merchant_id: user?.merchant?.id,
      },
    });

    const payload = {
      ...params,
      platform,
      shop_id,
      one_cc_prepay_cod_conversion: {
        enabled: isPrepayCODEnabled,
        configs: {
          discount: {
            type: !availDiscount ? DISCOUNT_TYPE.zero : discountType,
            discount_percentage: discount.percent !== '' ? discount.percent : 0,
            max_discount: discount?.maxDiscount ? discount.maxDiscount * 100 : 0,
            minimum_order_value: discount.minOrderValue * 100,
          },
          risk_category: getConvertRiskCategoryArray(convertRiskCategory, isManualReviewOpted),
          communication: {
            expire_seconds:
              validityType !== 'custom'
                ? parseInt(validityType, 10)
                : parseInt(getTimeInSeconds(durationVal), 10),
            methods: convertOn === 'both' ? ['whatsapp', 'checkout'] : [convertOn],
          },
        },
      },
    };

    updateConfigs(payload)
      .then(() => {
        showNotification({
          type: 'success',
          message: NOTIFICATION_MSGS.configSuccess,
          className: 'magic-cod-prepaid-notification',
        });
        setIsConfigSaved(true);
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_MSGS.error,
          className: 'magic-cod-prepaid-notification',
        });
      })
      .finally(() => {
        closeModal();
      });
  };

  const onAbort = () => {
    analyticsTrack({
      objectName: `1ccMdClickedDoNotSavePrepayConfigs`,
      actionName: 'clicked',
      screen: `confirmation modal l1`,
      properties: {
        merchant_id: user?.merchant?.id,
      },
    });
    closeModal();
  };

  const openCredentialsModal = () => {
    openModal({
      size: 'large',
      className: `woocommerceManualSettingModal`,
      component: (
        <CredentialsModal
          platform="woocommerce"
          submitCredentials={(payload) => addConfigs(payload)}
          modalDesc={CREDENTIALS_MODAL_DESC}
          customCloseModal={showAlertNotification}
        />
      ),
    });
  };

  const saveConfigs = () => {
    const { header, desc, affirmativeLabel, abortLabel } = SAVE_CONFIGS_CONFIRMATION_TEXTS;

    const onAffirm =
      !isManualReviewOpted && !isPrepayCODOpted && platform === 'woocommerce'
        ? openCredentialsModal
        : addConfigs;

    analyticsTrack({
      objectName: `1ccMdClickedSaveSettings`,
      actionName: 'clicked',
      screen: `magic prepay cod edit configurations l1`,
      properties: {
        merchant_id: user?.merchant?.id,
      },
    });

    openModal({
      size: 'small',
      className: `save-configs-confirmation-modal`,
      component: (
        <ConfirmationModal
          header={header}
          desc={desc}
          affirmativeLabel={affirmativeLabel}
          abortLabel={abortLabel}
          onAffirm={onAffirm}
          onAbort={onAbort}
        />
      ),
    });
  };

  useEffect(() => {
    const { hours: h, mins: m } = durationVal;
    const hours = parseInt(h, 10);
    const mins = parseInt(m, 10);

    switch (true) {
      case hours == 0 && mins < MIN_TIME:
        setDurationVal((prevState) => ({
          ...prevState,
          error: {
            hours: VALIDATION_MSGS.duration.minTimeError,
            mins: VALIDATION_MSGS.duration.minTimeError,
          },
        }));
        break;
      case isValidDuration(hours, mins):
        setDurationVal((prevState) => ({ ...prevState, error: { hours: null, mins: null } }));
        break;
      case hours >= MAX_HOURS && mins > 0:
        setDurationVal((prevState) => ({
          ...prevState,
          error: {
            hours: VALIDATION_MSGS.duration.maxTimeError,
            mins: VALIDATION_MSGS.duration.maxTimeError,
          },
        }));
        break;
      case mins > MAX_MINS && hours >= 0 && hours < MAX_HOURS:
        setDurationVal((prevState) => ({
          ...prevState,
          error: { hours: null, mins: VALIDATION_MSGS.duration.minutesError },
        }));
        break;
      case hours >= 0 && hours <= MAX_HOURS && durationVal.error.hours:
        setDurationVal((prevState) => ({
          ...prevState,
          error: { ...prevState.error, hours: null },
        }));
        break;
      case mins >= 0 && mins <= MAX_MINS && durationVal.error.mins:
        setDurationVal((prevState) => ({
          ...prevState,
          error: { ...prevState.error, mins: null },
        }));
        break;
      default:
        break;
    }
  }, [durationVal.hours, durationVal.mins]);

  return (
    <>
      <div className="configuration-container update-configs-view">
        {showPrepayCODToggle && (
          <PrepayCODToggle
            isPrepayCODEnabled={isPrepayCODEnabled}
            setIsPrepayCODEnabled={setIsPrepayCODEnabled}
            platform={platform}
            shopId={shop_id}
          />
        )}
        {isPrepayCODEnabled && (
          <>
            {isManualReviewOpted && (
              <ConvertCategoryField
                convertRiskCategory={convertRiskCategory}
                setConvertRiskCategory={setConvertRiskCategory}
              />
            )}
            <Discount
              discount={discount}
              setDiscount={setDiscount}
              discountType={discountType}
              setDiscountType={setDiscountType}
              availDiscount={availDiscount}
              setAvailDiscount={setAvailDiscount}
            />
            <hr />
            <LinkValidity
              validityType={validityType}
              setValidityType={setValidityType}
              durationVal={durationVal}
              setDurationVal={setDurationVal}
            />
            <hr />
            <ConversionPlatform
              convertOn={convertOn}
              setConvertOn={setConvertOn}
              platform={platform}
            />
          </>
        )}
      </div>
      {isPrepayCODEnabled && (
        <div className="cta-container">
          <Button.Primary
            type="button"
            className="btn btn-primary"
            onClick={saveConfigs}
            disabled={isCtaDisabled}
          >
            Save settings
          </Button.Primary>
        </div>
      )}
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      updateConfigs: updatePrepayCODConfigs,
      showNotification: displayNotification,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(CODPrepaidConfigs);
