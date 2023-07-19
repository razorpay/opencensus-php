import React from 'react';
import { connect } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';

import AddLinkModal from './AddLinkModal';
import RestrictWebsiteModal from './RestrictWebsiteModal';
import { MerchantProduct, Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import { trackCTAClick } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/events';
import {
  INTEGRATION_TITLE,
  PLATFORM_TITLE,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
import { bindActionCreators } from 'redux';

type AddLinkProps = {
  product: MerchantProduct;
  platform: Platform;
  openModal: any;
  closeModal: any;
  userHasKeyAccess: boolean;
};

const AddLink = ({
  product,
  platform,
  // actions from redux
  openModal,
  closeModal,
  userHasKeyAccess,
}: AddLinkProps): JSX.Element => {
  const openAddLinkModal = (): void => {
    trackCTAClick('Add Link', { paymentChannel: INTEGRATION_TITLE[platform], product });
    openModal({
      size: 'medium',
      component: userHasKeyAccess ? (
        <RestrictWebsiteModal closeModal={closeModal} />
      ) : (
        <AddLinkModal product={product} platform={platform} />
      ),
    });
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
    </div>
  );
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ ...ModalActions }, dispatch);

export default connect(null, mapDispatchToProps)(AddLink);
