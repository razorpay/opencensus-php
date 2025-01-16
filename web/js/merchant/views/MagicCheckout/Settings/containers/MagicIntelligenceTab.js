import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useState, useCallback } from 'react';

import CodIntelligenceToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/CodIntelligenceToggle';
import MagicIntelligence from 'merchant/views/MagicCheckout/MagicIntelligence';
import ManualReviewToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/ManualReviewToggle';
import ConfirmationModal, {
  DisplayNotificationTxt,
} from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';

import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { analyticsTrack } from 'common/utils/analytics';
import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';
import {
  PLATFORMS,
  MANUAL_REVIEW_MODAL,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { SWITCH_TEXTS, CREDENTIALS_MODAL } from 'merchant/views/MagicCheckout/Settings/constants';

const MagicIntelligenceTab = ({
  settings,
  updateSettings,
  openModal,
  closeModal,
  showNotification,
  merchantId,
  isPrepayCODEnabled,
}) => {
  const { cod_intelligence, platform, shop_id, manualControlCodOrder, rcodEnabled } = settings;
  const [codIntelligence, setCodIntelligence] = useState(cod_intelligence || false);
  const [codOrderControl, setCodOrderControl] = useState(manualControlCodOrder || false);
  const isMagicXPublicappCodEnabled = useMagicExperiment('magicx_publicapp_cod');

  const ReviewModal = MANUAL_REVIEW_MODAL[platform]?.component;

  const switchMode = useCallback(
    (toggleState) => {
      const modalAction = toggleState ? 'disabled' : 'enabled';
      const params = {
        platform,
        cod_intelligence: !codIntelligence,
        manual_control_cod_order: false,
      };

      if (platform === PLATFORMS.VALUES.SHOPIFY) {
        params.shop_id = shop_id;
      }

      updateSettings(params, false)
        .then(() => {
          showNotification({
            type: 'success',
            message: () => (
              <DisplayNotificationTxt
                notificationTxt={`COD Intelligence ${modalAction} successfully`}
              />
            ),
          });

          analyticsTrack({
            objectName: `codIntelligence${codIntelligence ? 'Disabled' : 'Enabled'}`,
            actionName: 'behav',
            screen: 'platform settings l1',
            properties: {
              platform,
              merchant_id: merchantId,
              action_source: 'user',
            },
          });

          manualControlCodOrder &&
            analyticsTrack({
              objectName: 'manualControlCodDisabled',
              actionName: 'behav',
              screen: 'platform settings l1',
              properties: {
                platform,
                merchant_id: merchantId,
                action_source: 'auto',
              },
            });

          setCodIntelligence((prevState) => !prevState);
          setCodOrderControl(false);
        })
        .finally(() => {
          closeModal();
        });
    },
    [codIntelligence, platform, shop_id, closeModal, showNotification],
  );

  const switchReviewMode = useCallback(
    (toggleState, payload = {}) => {
      const modalAction = toggleState ? 'disabled' : 'enabled';
      const params = {
        platform,
        manual_control_cod_order: !codOrderControl,
        cod_intelligence: false,
        ...payload,
      };

      if (platform === PLATFORMS.VALUES.SHOPIFY) {
        params.shop_id = shop_id;
      }

      updateSettings(params, false)
        .then(() => {
          showNotification({
            type: 'success',
            message: () => (
              <DisplayNotificationTxt
                notificationTxt={`Manual review ${modalAction} successfully`}
              />
            ),
          });

          analyticsTrack({
            objectName: `manualControlCod${manualControlCodOrder ? 'Disabled' : 'Enabled'}`,
            actionName: 'behav',
            screen: 'platform settings l1',
            properties: {
              platform,
              merchant_id: merchantId,
              action_source: 'user',
            },
          });

          codIntelligence &&
            analyticsTrack({
              objectName: 'codIntelligenceDisabled',
              actionName: 'behav',
              screen: 'platform settings l1',
              properties: {
                platform,
                merchant_id: merchantId,
                action_source: 'auto',
              },
            });

          setCodOrderControl((prevState) => !prevState);
          setCodIntelligence(false);
          closeModal();
        })
        .catch(() => {
          closeModal();
        });
    },
    [codOrderControl, platform, shop_id, closeModal, showNotification],
  );

  const switchReviewToggle = useCallback(
    (modalState) => {
      openModal({
        size: 'large',
        className: `${platform}ManualSettingModal`,
        component: (
          <ReviewModal
            platform={platform}
            submitCredentials={(payload) => switchReviewMode(modalState, payload)}
            modalDesc={CREDENTIALS_MODAL[platform]?.desc}
          />
        ),
      });
    },
    [openModal, platform, codOrderControl],
  );

  const getAction = (toggleState) => {
    return !toggleState && !isPrepayCODEnabled && platform !== 'shopify'
      ? () => switchReviewToggle(toggleState)
      : () => switchReviewMode(toggleState);
  };

  const getConfimationModalType = (modalSource, toggleState) => {
    const switchState = codIntelligence || codOrderControl ? 'Enabled' : 'Disabled';
    return !toggleState ? `${modalSource}${switchState}` : modalSource;
  };

  const onToggleClick = useCallback(
    (modalSource, toggleState) => {
      const modalType = getConfimationModalType(modalSource, toggleState);
      const modalAction =
        modalSource === 'codIntelligence' ? () => switchMode(toggleState) : getAction(toggleState);
      const modalState = !toggleState ? 'enable' : 'disable';
      const { header, desc, subText, secondaryCtaLabel, primaryCtaLabel } =
        SWITCH_TEXTS[modalState][modalType];

      openModal({
        size: 'small',
        className: `magicToggleConfirmationModal`,
        component: (
          <ConfirmationModal
            header={header}
            subText={subText}
            desc={desc}
            affirmativeLabel={primaryCtaLabel}
            abortLabel={secondaryCtaLabel}
            onAffirm={modalAction}
          />
        ),
      });
    },
    [openModal, codIntelligence, codOrderControl],
  );
  return (
    <div className="magic-intelligence">
      <div className="header-wrapper">
        <div className="font-20 font-bold heading">Reduce RTO orders with Magic Checkout</div>
        <div className="font-14 subtext">
          {isMagicXPublicappCodEnabled
            ? 'Disable COD option for high RTO risk customers'
            : 'Disable COD option or retrieve RTO risk details for high risk customers'}
        </div>
        <div className="magic-intelligence-toggle">
          <CodIntelligenceToggle
            checked={codIntelligence}
            switchMode={() => onToggleClick('codIntelligence', codIntelligence)}
            sopcMetafields={settings.sopc_metafields}
            rcodEnabled={rcodEnabled}
          />
          {!rcodEnabled && (
            <>
              <ManualReviewToggle
                checked={codOrderControl}
                switchMode={() => onToggleClick('manualReview', codOrderControl)}
              />
              <div className="rto-settings-info-container display-flex">
                <i className="i i-info-outline intelligence-tooltip font-normal" />
                <p className="info-text">Note: Only one configuration can be enabled at a time</p>
              </div>
            </>
          )}
        </div>
      </div>
      <div className="magic-intelligence-shiprocket">
        {!useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT) && (
          <MagicIntelligence isRCOD={rcodEnabled} />
        )}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  merchantId: state.config?.config?.id,
  isPrepayCODEnabled: state.magicCheckout?.one_cc_prepay_cod_conversion,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
      openModal,
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(MagicIntelligenceTab);
