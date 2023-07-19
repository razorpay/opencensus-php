import React, { useState } from 'react';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import { bindActionCreators, compose } from 'redux';
import AsyncButton from 'react-async-button';

import User from 'merchant/models/User';
import { required } from 'common/utils/validators';
import InputField from 'common/ui/Forms/InputField';
import { merchantFetch } from 'merchant/utils/ajax';
import { updateSession } from 'merchant/reducers/session';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import {
  trackAsyncResult,
  trackCTAClick,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/events';
import {
  INTEGRATION_TITLE,
  PLATFORM_TITLE,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
import { MerchantProduct, Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import { fetchMerchantPlugin } from 'merchant/reducers/plugins';
import WebsiteSucessModal from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/components/WebsiteSucessModal';

interface AddLinkModalProps {
  platform: Platform;
  product: MerchantProduct;
  user: any;
  openModal: any;
  closeModal: any;
  handleSubmit: any;
  updateSession: any;
  showNotification: any;
  fetchMerchantPlugin: any;
}

const AddLinkModal = ({
  platform,
  product,
  // state from redux
  user,
  // actions from redux
  handleSubmit,
  openModal,
  closeModal,
  showNotification,
  updateSession,
  fetchMerchantPlugin,
}: AddLinkModalProps): JSX.Element => {
  const [isSubmitting, setIsSubmitting] = useState(false);

  const trackProps = { paymentChannel: INTEGRATION_TITLE[platform], product };

  const showWebsiteSuccessModal = () => {
    if (!user.has_key_access && !user.isAccepted) {
      openModal({ size: 'medium', component: <WebsiteSucessModal closeModal={closeModal} /> });
    }
  };

  const onSubmit = (data) => {
    trackCTAClick('Save Link', { paymentChannel: INTEGRATION_TITLE[platform], product });
    setIsSubmitting(true);
    const payload = { [platform]: data.link.trim() };

    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      headers: {
        'content-type': 'application/json',
      },
      data: payload,
    })
      .then((response) => {
        trackAsyncResult('Save Link', { status: 'Success', ...trackProps });
        const { business_website, appstore_url, playstore_url } = response.data;
        const updatedUser = new User({ ...user, business_website, appstore_url, playstore_url });
        updateSession({ user: updatedUser });
        closeModal();
        showWebsiteSuccessModal();
        showNotification({
          type: 'success',
          message: 'Link added successfully',
        });
        if (platform === Platform.WEBSITE) {
          fetchMerchantPlugin({ merchantId: user.current });
        }
      })
      .catch(({ errors }) => {
        trackAsyncResult('Save Link', {
          status: 'Failure',
          failureReason: errors?.[0],
          ...trackProps,
        });
        showNotification({
          type: 'error',
          message: errors?.[0],
        });
      })
      .finally(() => setIsSubmitting(false));
  };

  return (
    <div className="add-link-modal-content">
      <button type="button" className="close" data-testid="close" onClick={closeModal}>
        <i className="i i-close" />
      </button>
      <div className="heading">Add your {PLATFORM_TITLE[platform]} link</div>
      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="form-group">
          <Field
            component={InputField}
            type="text"
            name="link"
            placeholder="http://"
            className="form-control"
            validate={required()}
          />
        </div>
        <AsyncButton
          type="submit"
          className="btn btn-primary btn-block"
          text={isSubmitting ? 'Saving...' : 'Save'}
          disabled={isSubmitting}
        />
      </form>
    </div>
  );
};

const mapStateToProps = (state) => ({ user: state.session.user });

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      ...NotificationsActions,
      updateSession,
      fetchMerchantPlugin,
    },
    dispatch,
  );

export default compose<any>(
  connect(mapStateToProps, mapDispatchToProps),
  reduxForm({
    form: 'AddLinkForm',
  }),
)(AddLinkModal);
