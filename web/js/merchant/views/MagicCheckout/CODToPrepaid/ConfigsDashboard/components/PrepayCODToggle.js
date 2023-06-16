import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import SwitchField from 'common/ui/Forms/SwitchField';
import Popover, { PopoverBody } from 'common/ui/Popover';
import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';

import { showNotification as displayNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { updatePrepayCODConfigs } from 'merchant/reducers/magicCheckout/prepayCOD/configDashboard/action';

import {
  POPOVER_INFO_TEXT,
  NOTIFICATION_MSGS,
  DISABLE_PREPAY_COD_CONFIRMATION_TEXTS,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';

const PrepayCODToggle = (props) => {
  const {
    isPrepayCODEnabled,
    setIsPrepayCODEnabled,
    openModal,
    closeModal,
    updateConfigs,
    showNotification,
    shopId: shop_id,
    platform,
  } = props;

  const disableConfig = () => {
    updateConfigs({
      platform,
      shop_id,
      one_cc_prepay_cod_conversion: {
        enabled: false,
      },
    })
      .then(() => {
        setIsPrepayCODEnabled(false);
        showNotification({
          type: 'success',
          message: NOTIFICATION_MSGS.disableSuccess,
        });
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: NOTIFICATION_MSGS.error,
        });
      })
      .finally(() => {
        closeModal();
      });
  };

  const disablePrepayCOD = () => {
    const { header, desc, affirmativeLabel, abortLabel } = DISABLE_PREPAY_COD_CONFIRMATION_TEXTS;

    openModal({
      size: 'small',
      className: `disable-prepay-cod-confirmation-modal`,
      component: (
        <ConfirmationModal
          header={header}
          desc={desc}
          affirmativeLabel={affirmativeLabel}
          abortLabel={abortLabel}
          onAffirm={disableConfig}
        />
      ),
    });
  };

  const enablePrepayCOD = () => {
    setIsPrepayCODEnabled((prevState) => !prevState);
  };

  const onToggle = () => {
    isPrepayCODEnabled ? disablePrepayCOD() : enablePrepayCOD();
  };

  return (
    <>
      <div className="config-box">
        <div className="configuration-label col-md-4">
          <label>
            Convert COD to prepaid orders
            <sup className="magic-checkout-required"> *</sup>
            <i className="i i-info-outline intelligence-tooltip font-normal">
              <Popover theme="dark">
                <PopoverBody>
                  <p>{POPOVER_INFO_TEXT.prepayCODToggle}</p>
                </PopoverBody>
              </Popover>
            </i>
          </label>
        </div>
        <div className="configuration-value col-md-8">
          <div className="display-flex discount-container">
            <div>
              <span className="toggler-btn">
                <SwitchField
                  checked={isPrepayCODEnabled}
                  type="prime"
                  onChange={() => onToggle()}
                />
                {isPrepayCODEnabled ? (
                  <b className="text-primary toggle-status">Enabled</b>
                ) : (
                  <b className="text-faded toggle-status">Disabled</b>
                )}
              </span>
            </div>
          </div>
        </div>
      </div>
      {isPrepayCODEnabled && <hr />}
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      openModal,
      closeModal,
      showNotification: displayNotification,
      updateConfigs: updatePrepayCODConfigs,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(PrepayCODToggle);
