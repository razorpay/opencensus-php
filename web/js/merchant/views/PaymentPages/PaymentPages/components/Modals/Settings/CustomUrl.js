import { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import Input from 'common/new-ui/Input';
import DomainAddressModal from '../CustomDomain/DomainAddress';
import RemoveDomainModal from '../CustomDomain/RemoveDomain';
import Spinner from 'common/ui/Spinner';

import { validateSlug } from 'common/utils/validators';
import track from '../../../Wysiwyg/track';

import { fetchCustomDomainDetails } from '../../../../../../reducers/wysiwyg';

const WRAPPER_CLASS = 'settings-section custom-url';
const INPUT_NAME = 'slug';
const LABEL = 'Choose custom URL for this page';
const RZP_PAGES_URL = 'https://pages.razorpay.com/';
const INPUT_CLASS = 'Input--vTop';
const MAX_LENGTH = '30';
const CUSTOM_URL_DISABLED_INFO = (
  <div style={{ marginTop: 4, fontSize: 13 }}>
    Custom URL is only available in <b>Live Mode</b>
  </div>
);

const CustomURL = ({
  slug,
  isTestMode,
  paymentPageId,
  openModal,
  closeModal,
  user,
  customDomainData,
  fetchCustomDomainDetails,
  paymentPageCustomUrl,
}) => {
  const isCustomDomainFeatureEnabled = user.isPaymentPageCustomDomainEnabled;
  const { isLoading, isError, value } = customDomainData; // domain setup at a global level (redux)
  const isCustomDomainSetup = !!value;

  /* 
    setting as custom domain only when both settings.custom_domain
    and merchant level custom_domain setup exists
  */
  const [urlType, setUrlType] = useState(paymentPageCustomUrl && value ? 'custom' : 'rzp'); // paymentPageCustomUrl - if custom domain being used at page level

  useEffect(() => {
    setUrlType(paymentPageCustomUrl && value ? 'custom' : 'rzp');
  }, [paymentPageCustomUrl, value]);

  useEffect(() => {
    if (isCustomDomainFeatureEnabled) {
      fetchCustomDomainDetails();
    }
  }, [isCustomDomainFeatureEnabled, fetchCustomDomainDetails]);

  const openCustomDomainModal = () => {
    openModal({
      size: 'medium',
      className: 'pp-custom-domain',
      component: <DomainAddressModal openModal={openModal} closeModal={closeModal} />,
    });
  };

  const openRemoveModal = () => {
    openModal({
      size: 'medium',
      className: 'pp-custom-domain',
      component: (
        <RemoveDomainModal openModal={openModal} closeModal={closeModal} domainName={value} />
      ),
    });
  };

  // logic to calculate left padding based on length of addonValueBefore
  const calculateDynamicPadding = () => {
    if (urlType === 'rzp') return 190; // for the case https://pages.razorpay.com/

    const tempSpanElement = document.createElement('span');
    const settingsContainer = document.querySelector('.Modal-mask--paymentpages-settings');

    tempSpanElement.innerText = `https://${value}/`;
    settingsContainer && settingsContainer.append(tempSpanElement);

    const paddingLeft = 13 + tempSpanElement.getBoundingClientRect().width;
    tempSpanElement.remove();

    return paddingLeft;
  };

  const validator = (val) => {
    const isEditMode = !!paymentPageId;
    const toValidate = !isTestMode && isEditMode;
    const checkMinLength = urlType === 'rzp';

    if (toValidate) {
      if (val && !validateSlug(val.trim())) {
        return 'Please enter valid Url';
      }

      /*
        min length being checked only in the case of when pages.razorpay.com domain is being 
        used as while using a custom domain, the slug can be of less than 4 characters as well
      */
      if (checkMinLength && val.length < 4) {
        return 'Url must be at least 4 characters long';
      } else if (val.length > 30) {
        return 'Url must be maximum 30 characters long';
      }
    }
    return '';
  };

  /*
    - for the flow without custom domain enabled (earlier code)
    - keeping it separate for ease in reading code and avoiding
      checks related to the api call
  */
  if (!isCustomDomainFeatureEnabled) {
    return (
      <div class={WRAPPER_CLASS} tabIndex={-1}>
        <Input
          name={INPUT_NAME}
          class={INPUT_CLASS}
          label={LABEL}
          defaultValue={slug}
          addonValueBefore={RZP_PAGES_URL}
          disabled={isTestMode}
          validator={validator}
          onBlur={track.settings.enterCustomUrl}
          maxLength={MAX_LENGTH}
        />

        {isTestMode && CUSTOM_URL_DISABLED_INFO}
      </div>
    );
  }

  return (
    <div class={WRAPPER_CLASS} tabIndex={-1}>
      {isLoading ? (
        <>
          <div class="Input-label m-b">{LABEL}</div>
          <div class="text-center">
            <Spinner />
          </div>
        </>
      ) : isError ? (
        <>
          <div class="Input-label m-b">{LABEL}</div>
          <div class="text-danger">
            Failed to fetch custom domain details for this page, please try again later
          </div>
        </>
      ) : (
        <>
          <Input
            name={INPUT_NAME}
            autoRender
            class={INPUT_CLASS}
            label={LABEL}
            defaultValue={slug}
            addonValueBefore={urlType === 'rzp' ? RZP_PAGES_URL : `https://${value}/`}
            style={{ paddingLeft: calculateDynamicPadding() }}
            disabled={isTestMode}
            validator={validator}
            onBlur={track.settings.enterCustomUrl}
            extraChildren={
              isCustomDomainSetup && (
                <Input.Radio
                  name="domainType"
                  options={[
                    { label: 'Use my domain', value: 'custom' },
                    { label: `Use Razorpay's domain`, value: 'rzp' },
                  ]}
                  class="Input--vTop"
                  defaultValue={urlType}
                  onChange={(e) => {
                    setUrlType(e.target.value);
                  }}
                  key={urlType} // passing key here to force rerender on urlType change
                />
              )
            }
            maxLength={MAX_LENGTH}
          />
          {!isTestMode &&
            (!isCustomDomainSetup ? (
              <div class="cta-section">
                <div class="body">
                  <span class="badge bg-success hidden-xs m-r">New</span>
                  Use your domain in this URL
                </div>
                <span class="action">
                  <button
                    class="btn Button--primary--invert Button"
                    onClick={openCustomDomainModal}
                    type="button"
                  >
                    Connect Domain
                  </button>
                </span>
              </div>
            ) : (
              urlType === 'custom' && (
                <div class="remove-domain" onClick={openRemoveModal}>
                  <i class="i i-delete" />
                  Remove domain
                </div>
              )
            ))}
          {isTestMode && CUSTOM_URL_DISABLED_INFO}
        </>
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  customDomainData: state.wysiwyg.customDomain,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => ({
  fetchCustomDomainDetails: bindActionCreators(fetchCustomDomainDetails, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(CustomURL);
