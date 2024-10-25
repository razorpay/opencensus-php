import React, { useState } from 'react';
import { connect } from 'react-redux';
import { useNavigate } from 'react-router-dom';
import { bindActionCreators } from 'redux';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import { fetchMerchantPlugin } from 'merchant/reducers/plugins';
import { FLOWS } from 'merchant/views/Account/Profile/components/WebsiteSelfServe/Constants';
import InitiateWebsiteChange from 'merchant/views/Account/Profile/components/WebsiteSelfServe/InitiateWebsiteChange';
import WebsiteSubmitModal from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/WebsiteSubmitModal';
import {
  INTEGRATION_TITLE,
  PLATFORM_TITLE,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
import { trackCTAClick } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/events';
import { MerchantProduct, Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import * as ModalActions from 'merchant_common/reducers/modals';

import AddLinkModal from './AddLinkModal';
import RestrictWebsiteModal from './RestrictWebsiteModal';

type AddLinkProps = {
  product: MerchantProduct;
  platform: Platform;
  openModal: any;
  closeModal: any;
  userHasKeyAccess: boolean;
  user: User;
  fetchMerchantPlugin: (args) => void;
};

const AddLink = ({
  product,
  platform,
  // actions from redux
  openModal,
  closeModal,
  userHasKeyAccess,
  user,
  fetchMerchantPlugin,
}: AddLinkProps): JSX.Element => {
  const navigate = useNavigate();
  const [isSubmitWebsiteModalVisible, setIsSubmitWebsiteModalVisible] = useState(false);
  const {
    abExperiments: { show_v2_website_flow },
  } = useSplitzService();

  const isV2WebsiteFlowEnabled = isExperimentEnabled(show_v2_website_flow);

  const openAddLinkModal = (): void => {
    if (user.activation_status === 'activated') {
      navigate('/website-app-settings/business-website-details');
    } else {
      trackCTAClick('Add Link', { paymentChannel: INTEGRATION_TITLE[platform], product });
      openModal({
        size: 'medium',
        component: userHasKeyAccess ? (
          <RestrictWebsiteModal closeModal={closeModal} />
        ) : isV2WebsiteFlowEnabled ? (
          <InitiateWebsiteChange
            closeModal={closeModal}
            flowType={FLOWS.BUSINESS_WEBSITE}
            openModal={openModal}
            openNewModal={() => {
              closeModal();
              setIsSubmitWebsiteModalVisible(true);
            }}
            user={user}
          />
        ) : (
          <AddLinkModal product={product} platform={platform} />
        ),
      });
    }
  };
  return (
    <div className="keys-plugins-section--empty">
      <i className="i i-anchor" />
      <div className="heading">Add your {PLATFORM_TITLE[platform]} link</div>
      <div className="subheading">
        For your account security, we’ll allow payments only on the given links
      </div>
      <button className="btn btn-primary" onClick={openAddLinkModal}>
        Add link
      </button>

      <WebsiteSubmitModal
        isOpen={isSubmitWebsiteModalVisible}
        onDismiss={() => setIsSubmitWebsiteModalVisible(false)}
        refetchData={() => fetchMerchantPlugin({ merchantId: user.current })}
      />
    </div>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators({ ...ModalActions, fetchMerchantPlugin }, dispatch);

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  mapDispatchToProps,
)(AddLink);
