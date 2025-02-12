import { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import lazy from 'merchant/routes/LazyLoader';

import Input from 'common/new-ui/Input';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import { validateSlug } from 'common/utils/validators';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

import { fetchCustomDomainDetails, fetchCustomDomainPlanDetails } from 'merchant/reducers/wysiwyg';

import LockImage from 'assets/payment_pages/lock-outline.svg';

const PlansListModal = lazy(() =>
  import(/* webpackChunkName: 'PaymentPagesPlansListModal' */ '../CustomDomain/PlansList'),
);
const RemoveDomainModal = lazy(() =>
  import(/* webpackChunkName: 'PaymentPagesRemoveDomainModal' */ '../CustomDomain/RemoveDomain'),
);
const PlanDetailsModal = lazy(() =>
  import(/* webpackChunkName: 'PaymentPagesPlanDetailsModal' */ '../CustomDomain/PlanDetails'),
);
const DomainAddress = lazy(() =>
  import(/* webpackChunkName: 'PaymentPagesDomainAddress' */ '../CustomDomain/DomainAddress'),
);

const WRAPPER_CLASS = 'settings-section custom-url';
const INPUT_NAME = 'slug';
const LABEL = 'URL of this page';
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
  fetchCustomDomainPlanDetails,
  paymentPageCustomUrl,
}) => {
  const { value, planDetails } = customDomainData; // domain setup at a global level (redux)

  const isCustomDomainFeatureEnabled = user.isPaymentPageCustomDomainEnabled;
  const isCustomDomainSetup = !!value;
  const isPlanActive = !!planDetails.id;

  /* 
    setting as custom domain only when both settings.custom_domain
    and merchant level custom_domain setup exists
  */
  const [urlType, setUrlType] = useState(paymentPageCustomUrl && value ? 'custom' : 'rzp'); // paymentPageCustomUrl - if custom domain being used at page level
  const [isLoading, setLoading] = useState(true);
  const [isError, setError] = useState(false);

  useEffect(() => {
    setUrlType(paymentPageCustomUrl && value ? 'custom' : 'rzp');
  }, [paymentPageCustomUrl, value]);

  useEffect(() => {
    if (isCustomDomainFeatureEnabled) {
      setLoading(true);

      Promise.all([fetchCustomDomainDetails(), fetchCustomDomainPlanDetails()])
        .then(() => {})
        .catch(() => {
          setError(true);
        })
        .finally(() => {
          setLoading(false);
        });
    }
  }, [isCustomDomainFeatureEnabled, fetchCustomDomainDetails, fetchCustomDomainPlanDetails]);

  const handleConnectDomain = () => {
    /* 
      if mx has an active plan, then directly open domain address 
      modal else open the plans list modal so that they can choose a plan
      and then continue setting up the custom domain
    */
    if (isPlanActive) {
      openModal({
        size: 'medium',
        className: 'pp-custom-domain',
        component: (
          <SuspenseWithLoader>
            <DomainAddress
              openModal={openModal}
              closeModal={closeModal}
              planDetails={planDetails}
            />
          </SuspenseWithLoader>
        ),
      });
    } else {
      openModal({
        size: 'medium',
        className: 'pp-custom-domain',
        component: (
          <SuspenseWithLoader>
            <PlansListModal openModal={openModal} closeModal={closeModal} />
          </SuspenseWithLoader>
        ),
      });
    }

    track.settings.clickConnectDomain(isPlanActive);
  };

  const handleRemoveDomain = () => {
    openModal({
      size: 'medium',
      className: 'pp-custom-domain',
      component: (
        <SuspenseWithLoader>
          <RemoveDomainModal
            openModal={openModal}
            closeModal={closeModal}
            domainName={value}
            nextBillingDate={planDetails.next_billing_at}
          />
        </SuspenseWithLoader>
      ),
    });

    track.settings.removeDomainClick();
  };

  const handlePlanDetails = () => {
    openModal({
      size: 'medium',
      className: 'pp-custom-domain',
      component: (
        <SuspenseWithLoader>
          <PlanDetailsModal planDetails={planDetails} closeModal={closeModal} />
        </SuspenseWithLoader>
      ),
    });

    track.settings.clickPlanDetails();
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
      <div className={WRAPPER_CLASS} tabIndex={-1}>
        <Input
          name={INPUT_NAME}
          className={INPUT_CLASS}
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
    <div className={WRAPPER_CLASS} tabIndex={-1}>
      {isLoading ? (
        <>
          <div className="Input-label m-b">{LABEL}</div>
          <div className="text-center">
            <Spinner />
          </div>
        </>
      ) : isError ? (
        <>
          <div className="Input-label m-b">{LABEL}</div>
          <div className="text-danger">
            Failed to fetch custom domain details for this page, please try again later
          </div>
        </>
      ) : (
        <>
          <Input
            name={INPUT_NAME}
            autoRender
            className={INPUT_CLASS}
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
                  className="Input--vTop"
                  defaultValue={urlType}
                  onChange={(e) => {
                    setUrlType(e.target.value);

                    track.settings.clickDomainType(e.target.value);
                  }}
                  key={urlType} // passing key here to force rerender on urlType change
                />
              )
            }
            maxLength={MAX_LENGTH}
          />
          {!isTestMode &&
            (!isCustomDomainSetup ? (
              <>
                <div className="or-separator">- OR -</div>
                <div className="cta-section">
                  <div className="body">
                    <span className="lock-wrapper">
                      <img src={LockImage} alt="lock" width="24px" height="24px" />
                      <Popover
                        align="bottom"
                        theme="dark"
                        parentQuerySelector=".Modal-mask--paymentpages-settings .Modal-body"
                      >
                        <PopoverBody>
                          - Charges apply - <br />
                          This is a pro feature
                        </PopoverBody>
                      </Popover>
                    </span>
                    <span>Already have a domain?</span>
                  </div>
                  <span className="action">
                    <button
                      className="Button--primary--invert Button"
                      onClick={handleConnectDomain}
                      type="button"
                    >
                      Connect Domain
                    </button>
                  </span>
                </div>
              </>
            ) : (
              urlType === 'custom' && (
                <div className="custom-url-options">
                  <div className="remove-domain" onClick={handleRemoveDomain}>
                    <i className="i i-delete-outline" />
                    Remove domain
                  </div>
                  <div onClick={handlePlanDetails}>
                    <i className="i i-star-outline" />
                    Plan details
                  </div>
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
  fetchCustomDomainPlanDetails: bindActionCreators(fetchCustomDomainPlanDetails, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(CustomURL);
