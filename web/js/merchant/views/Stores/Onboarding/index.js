import { useState, useEffect } from 'react';
import { connect } from 'react-redux';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import StoreCreatedModal from '../components/StoreCreatedModal';

import { createStore, hideSuccessModal } from '../../../reducers/storefront';
import { showNotification } from 'merchant_common/reducers/notifications';
import { slugValidator, storeNameValidator } from '../helpers';

import { createStoreEntity } from '../model';

import track from './track';

const StoreOnboarding = (props) => {
  const [step, setStep] = useState(1);
  const [storeName, setStoreName] = useState('');
  const [slug, setSlug] = useState('');
  const [isGettingStarted, setIsGettingStarted] = useState(false);
  const [isStep1Disabled, setIsStep1Disabled] = useState(true);
  const [isStep2Disabled, setIsStep2Disabled] = useState(true);

  useEffect(() => {
    track.open();
  }, []);

  const handleStorenameChange = (e) => {
    setStoreName(e.target.value);

    setTimeout(() => {
      const storeNameElement = document.querySelector(
        '.store--onboarding-right .Input--stores-name',
      );

      if (storeNameElement) {
        const _isStep1Disabled = document.querySelectorAll(
          '.store--onboarding-right .Input--stores-name.is-invalid',
        ).length;
        setIsStep1Disabled(_isStep1Disabled);
      }
    });
  };

  const handleSlugChange = (e) => {
    setSlug(e.target.value);

    setTimeout(() => {
      const slugNameElement = document.querySelector(
        '.store--onboarding-right .Input--stores-slug',
      );

      if (slugNameElement) {
        const _isStep2Disabled = document.querySelectorAll(
          '.store--onboarding-right .Input--stores-slug.is-invalid',
        ).length;
        setIsStep2Disabled(_isStep2Disabled);
      }
    });
  };

  const handleStep1 = () => {
    setStep(2);
    track.saveAndProceedBtn();
  };

  const handleCreateStore = () => {
    track.createStoreBtn();

    return createStoreEntity({
      title: storeName,
      description: '',
      slug,
    })
      .then((res) => {
        // open success modal, update redux store
        props.createStore(res);
      })
      .catch((err) => {
        // show error message
        props.showNotification({
          type: 'error',
          message: err.errors[0],
        });
      });
  };

  return (
    <div className="store--onboarding-container">
      {props.isSuccessModal && <StoreCreatedModal hideSuccessModal={props.hideSuccessModal} />}
      <div
        className={`store--onboarding-left ${isGettingStarted ? 'store--onboarding-bypass' : ''}`}
      >
        <div className="store--onboarding-preheading">Create your Store with</div>
        <h1>Razorpay Stores</h1>
        <div className="divider" />
        <img
          src="/dist/css/assets/stores/stores-onboarding-banner.svg"
          alt="stores-onboarding-banner"
        />
        <div className="store--onboarding-description">
          <div>Go online in less than 5 minutes.</div>
          <div>
            List your products/services & collect payments seamlessly using Razorpay platform
          </div>
        </div>
        <a href="#">
          <i className="i-help-outline mr-5" />
          Learn more
        </a>
        <Button.Primary
          className="store--onboarding-bypass-button"
          onClick={() => setIsGettingStarted(true)}
        >
          Get started
        </Button.Primary>
      </div>
      <div
        className={`store--onboarding-right ${isGettingStarted ? 'store--onboarding-bypass' : ''}`}
      >
        <div className="store--onboarding-header">
          <span>Creating your Store</span>
          <span>Step {step} of 2</span>
        </div>
        {step === 1 ? (
          <OnboardingStep1
            storeName={storeName}
            handleStorenameChange={handleStorenameChange}
            handleStep={handleStep1}
            disabled={isStep1Disabled}
          />
        ) : step === 2 ? (
          <OnboardingStep2
            slug={slug}
            handleSlugChange={handleSlugChange}
            handleStep={handleCreateStore}
            prevStep={() => setStep(1)}
            disabled={isStep2Disabled}
          />
        ) : null}
      </div>
    </div>
  );
};

const OnboardingStep1 = ({ storeName, handleStorenameChange, handleStep, disabled }) => {
  return (
    <div className="store--onboarding-body">
      <div className="store--onboarding-title">What’s your Store’s name ?</div>
      <div className="store--onboarding-subtitle">
        This will be used as your website’s title (can be changed later, if required)
      </div>
      <Input
        autoRender
        placeholder="Your store name"
        value={storeName}
        className="Input--stores-name"
        onChange={handleStorenameChange}
        description={<div>{storeName ? storeName.length : 0} / 40</div>}
        validator={storeNameValidator}
      />
      <div className="store--onboarding-right-bottom">
        <Button.Primary onClick={handleStep} disabled={disabled}>
          Save & Proceed
        </Button.Primary>
      </div>
    </div>
  );
};

const OnboardingStep2 = ({ slug, handleSlugChange, handleStep, prevStep, disabled }) => {
  return (
    <div className="store--onboarding-body">
      <div className="store--onboarding-title">Your Store’s Website link</div>
      <div className="store--onboarding-subtitle">
        Choose the link that your customers will use to visit your store.
      </div>
      <Input
        value={slug}
        onChange={handleSlugChange}
        className="Input--stores-slug"
        addonValueBefore="http://stores.razorpay.com/"
        description={<div>{slug ? slug.length : 0} / 30</div>}
        validator={slugValidator}
      />
      <div className="store--onboarding-right-bottom">
        <AsyncBtn.Primary onClick={handleStep} disabled={disabled}>
          Create my store
        </AsyncBtn.Primary>
        <Button.Transparent onClick={prevStep}>Go back</Button.Transparent>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  isSuccessModal: state.storefront.entity.isSuccessModal,
});

const mapDispatchToProps = (dispatch) => ({
  showNotification: (data) => dispatch(showNotification(data)),
  createStore: (data) => dispatch(createStore(data)),
  hideSuccessModal: () => dispatch(hideSuccessModal()),
});

export default connect(mapStateToProps, mapDispatchToProps)(StoreOnboarding);
