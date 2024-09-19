import { useState } from 'react';
import {
  Box,
  Button,
  Radio,
  RadioGroup,
  TextInput,
  TextArea,
  PasswordInput,
  Link,
  FileUpload as BladeFileUpload,
  Text,
  ExternalLinkIcon,
} from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { useMobile } from 'common/hooks/useMobile';
import Input from 'common/new-ui/Input';
import ModalHeader from 'common/ui/ModalHeader';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, autoPrefixUrls } from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import FileUpload from 'merchant/components/File/Upload';
import { fetchWorkflowStatus as fetchWorkflowStatusReducer } from 'merchant/reducers/workflows';
import { merchantFetch } from 'merchant/utils/ajax';
import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { useBusinessWebsiteRevamp } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/utils';
import SuggestionsBox from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/components/SuggestionsBox';
import useModalComponents from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/hooks/useModalComponents';
import { ValidationState } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/types';
import { snapPoints } from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import { FLOWS, FORM_FIELDS } from './Constants';
import { isValidWebsite } from 'common/utils/validators';

const WebsiteInputField = ({ name, validator, errorMessage, setErrorMessages, ...rest }) => (
  <Box display="flex" flexDirection="column" gap="spacing.2">
    <TextInput
      isRequired
      necessityIndicator="required"
      name={name}
      onFocus={() => setErrorMessages((prev) => ({ ...prev, [name]: '' }))}
      onBlur={({ value }) => validator(name, value)}
      errorText={errorMessage}
      validationState={errorMessage ? ValidationState.ERROR : ValidationState.NONE}
      {...rest}
    />
  </Box>
);

function WebsiteFields({
  flowType,
  handleFileChange,
  onBiggerFileSize,
  onCloseClick,
  file,
  validator,
  shouldShowV2,
  errorMessages,
  setErrorMessages,
}) {
  const isBusinessWebsiteRevamp = useBusinessWebsiteRevamp();
  const shouldShowBusinessWebsiteFields =
    isBusinessWebsiteRevamp && flowType === FLOWS.BUSINESS_WEBSITE;

  return shouldShowV2 ? (
    <>
      {shouldShowBusinessWebsiteFields ? null : (
        <>
          <WebsiteInputField
            label={FORM_FIELDS.ABOUT_US.label}
            name={FORM_FIELDS.ABOUT_US.name}
            validator={validator}
            errorMessage={errorMessages[FORM_FIELDS.ABOUT_US.name]}
            setErrorMessages={setErrorMessages}
          />

          <WebsiteInputField
            label={FORM_FIELDS.PRICING_DETAILS.label}
            name={FORM_FIELDS.PRICING_DETAILS.name}
            validator={validator}
            errorMessage={errorMessages[FORM_FIELDS.PRICING_DETAILS.name]}
            helpText="In case of multiple product pricing pages, please share a URL for any one of them"
            setErrorMessages={setErrorMessages}
          />
        </>
      )}
      {shouldShowBusinessWebsiteFields ? (
        <WebsiteInputField
          label={FORM_FIELDS.SHIPPING_POLICY.label}
          name={FORM_FIELDS.SHIPPING_POLICY.name}
          validator={validator}
          errorMessage={errorMessages[FORM_FIELDS.SHIPPING_POLICY.name]}
          setErrorMessages={setErrorMessages}
        />
      ) : null}

      <WebsiteInputField
        label={FORM_FIELDS.CONTACT_US.label}
        name={FORM_FIELDS.CONTACT_US.name}
        validator={validator}
        errorMessage={errorMessages[FORM_FIELDS.CONTACT_US.name]}
        setErrorMessages={setErrorMessages}
      />

      <WebsiteInputField
        label={FORM_FIELDS.TNC.label}
        name={FORM_FIELDS.TNC.name}
        validator={validator}
        errorMessage={errorMessages[FORM_FIELDS.TNC.name]}
        setErrorMessages={setErrorMessages}
      />

      <WebsiteInputField
        label={FORM_FIELDS.PRIVACY_POLICY.label}
        name={FORM_FIELDS.PRIVACY_POLICY.name}
        validator={validator}
        errorMessage={errorMessages[FORM_FIELDS.PRIVACY_POLICY.name]}
        setErrorMessages={setErrorMessages}
      />

      <WebsiteInputField
        label={FORM_FIELDS.REFUND_POLICY.label}
        name={FORM_FIELDS.REFUND_POLICY.name}
        validator={validator}
        errorMessage={errorMessages[FORM_FIELDS.REFUND_POLICY.name]}
        setErrorMessages={setErrorMessages}
      />

      {flowType === FLOWS.ADDITIONAL_WEBSITE && (
        <BladeFileUpload
          label="Upload Invoice"
          accept=".jpg, .pdf, .png"
          helpText="You can only upload .jpg, .pdf, or .png file"
          onRemove={onCloseClick}
          onChange={({ fileList }) => handleFileChange(fileList[0])}
          maxSize={1048576}
        />
      )}
    </>
  ) : (
    <>
      {shouldShowBusinessWebsiteFields ? null : (
        <>
          <Input
            required
            label={FORM_FIELDS.ABOUT_US.label}
            name={FORM_FIELDS.ABOUT_US.name}
            validator={(input) => {
              return validator(FORM_FIELDS.ABOUT_US.name, input);
            }}
          />
          <Input
            required
            label={
              <>
                {FORM_FIELDS.PRICING_DETAILS.label}
                <small class="help-content">
                  <i class="i i-info-circle" />
                  <Popover align="top" theme="dark" parentQuerySelector=".modal-body">
                    <PopoverBody>
                      <div>
                        In case of multiple product pricing pages, please share a URL for any one of
                        them
                      </div>
                    </PopoverBody>
                  </Popover>
                </small>
                {}
              </>
            }
            name={FORM_FIELDS.PRICING_DETAILS.name}
            validator={(input) => {
              return validator(FORM_FIELDS.PRICING_DETAILS.name, input);
            }}
          />
        </>
      )}
      {shouldShowBusinessWebsiteFields ? (
        <Input
          required
          label={FORM_FIELDS.SHIPPING_POLICY.label}
          name={FORM_FIELDS.SHIPPING_POLICY.name}
          validator={(input) => {
            return validator(FORM_FIELDS.SHIPPING_POLICY.name, input);
          }}
        />
      ) : null}
      <Input
        required
        label={FORM_FIELDS.CONTACT_US.label}
        name={FORM_FIELDS.CONTACT_US.name}
        validator={(input) => {
          return validator(FORM_FIELDS.CONTACT_US.name, input);
        }}
      />
      <Input
        required
        label={
          <a target="_blank" href={FORM_FIELDS.TNC.href} rel="noreferrer noopener">
            {FORM_FIELDS.TNC.label}
          </a>
        }
        name={FORM_FIELDS.TNC.name}
        validator={(input) => {
          return validator(FORM_FIELDS.TNC.name, input);
        }}
      />
      <Input
        required
        label={
          <a target="_blank" href={FORM_FIELDS.PRIVACY_POLICY.href} rel="noreferrer noopener">
            {FORM_FIELDS.PRIVACY_POLICY.label}
          </a>
        }
        name={FORM_FIELDS.PRIVACY_POLICY.name}
        validator={(input) => {
          return validator(FORM_FIELDS.PRIVACY_POLICY.name, input);
        }}
      />

      <Input
        required
        label={
          <a target="_blank" href={FORM_FIELDS.REFUND_POLICY.href} rel="noreferrer noopener">
            {FORM_FIELDS.REFUND_POLICY.label}
          </a>
        }
        name={FORM_FIELDS.REFUND_POLICY.name}
        validator={(input) => {
          return validator(FORM_FIELDS.REFUND_POLICY.name, input);
        }}
      />
      {flowType === FLOWS.ADDITIONAL_WEBSITE && (
        <div class="upload-invoice">
          <label>Upload invoice</label>
          <FileUpload
            accept={['jpg', 'png', 'pdf']}
            maxSize={1048576} // 1 MB
            showCloseBtn
            showFileSize={false}
            showAcceptInfo={false}
            onBiggerFileSize={onBiggerFileSize}
            onFileChange={handleFileChange}
            onCloseClick={onCloseClick}
          />
          {file?.message ? <label class="notify-error">{file.message}</label> : null}
        </div>
      )}
    </>
  );
}

function UpdateWebsiteDetails(props) {
  const isMobile = useMobile();
  const [errorMessages, setErrorMessages] = useState({
    url: '',
    ...Object.values(FORM_FIELDS).reduce((acc, { name }) => ({ ...acc, [name]: '' }), {}),
  });
  const [type, settype] = useState('website');
  const [doesNeedCreds, setdoesNeedCreds] = useState(true);
  const [file, setfile] = useState(null);
  const [isReasonValid, setisReasonValid] = useState(null); // Validity => minimum 100 words
  const [isLinkValid, setisLinkValid] = useState(true); // Validity => should not be an already existing one
  const isBusinessWebsiteRevamp = useBusinessWebsiteRevamp();
  const { Modal, ModalBody, ModalHeader: BladeModalHeader } = useModalComponents(isMobile);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [areMetaUrlsValid, setareMetaUrlsValid] = useState(() => {
    if (isBusinessWebsiteRevamp && props.flowType === FLOWS.BUSINESS_WEBSITE) {
      return {
        shipping_policy: true,
        contact_us: true,
        tnc: true,
        privacy_policy: true,
        refund_policy: true,
      };
    } else {
      return {
        about_us: true,
        contact_us: true,
        tnc: true,
        pricing_details: true,
        privacy_policy: true,
        refund_policy: true,
      };
    }
  });

  const submitBusinessDetails = async (formFieldValues) => {
    const payload = {};
    let urlDetails = {};

    if (type === 'website') {
      // Gather all field values into payload
      Object.keys(formFieldValues).forEach((key) => {
        const isKeyCred = ['username', 'password'].includes(key);
        const value = formFieldValues[key];
        payload[`business_website_${key}`] = isKeyCred ? value : autoPrefixUrls(value, true);
      });

      if (isBusinessWebsiteRevamp) {
        payload.version = 'v2';
      }

      // If creds are not checked, removing these keys
      if (!doesNeedCreds) {
        delete payload.business_website_username;
        delete payload.business_website_password;
      }

      urlDetails = { ...payload };
      // Removing creds from analytics
      delete urlDetails.business_website_username;
      delete urlDetails.business_website_password;
    } else {
      payload.business_app_url = autoPrefixUrls(formFieldValues.app_url, true);
      urlDetails = { ...payload };

      if (doesNeedCreds) {
        payload.business_app_username = formFieldValues.username;
        payload.business_app_password = formFieldValues.password;
      }
    }

    const { user } = props;
    if (urlDetails?.business_website_main_page === user?.business_website) {
      props.showNotification({
        type: 'error',
        message: `${type} Updating with same detail, please change main website`,
      });
      return;
    }
    analyticsTrack({
      objectName: `Website submit`,
      actionName: 'clicked',
      screen: 'My account',
      properties: {
        flow: user.has_key_access ? 'Website edit' : 'Website add',
        isWebsiteUpdateV2: props.shouldShowV2,
        currentWebsite: `${user.business_website}`,
        urlDetails,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    try {
      const response = await merchantFetch({
        url: `merchant/save_business_website/${type}`,
        method: 'POST',
        mode: 'live',
        data: payload,
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response) {
        props.showNotification({
          type: 'success',
          message: `${type} submitted successfully`,
        });
        props.fetchWorkflowStatus(WORKFLOW_TYPES.UPDATE_BUSINESS_WEBSITE);
        props.closeModal();
        props.onDismiss?.();
        analyticsTrack({
          objectName: `Website submit result`,
          actionName: 'Submit request',
          screen: 'My account',
          properties: {
            flow: user.has_key_access ? 'Website edit' : 'Website add',
            isWebsiteUpdateV2: props.shouldShowV2,
            result: `Success`,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      }
    } catch ({ errors }) {
      props.showNotification({
        type: 'error',
        message: errors,
      });

      analyticsTrack({
        objectName: `Website submit result`,
        actionName: 'Submit request',
        screen: 'My account',
        properties: {
          flow: user.has_key_access ? 'Website edit' : 'Website add',
          isWebsiteUpdateV2: props.shouldShowV2,
          result: `Failure`,
          failureReason: `${errors}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  const submitAdditionalDomainDetails = async (formFieldValues) => {
    const formData = new FormData();

    if (type === 'website') {
      Object.keys(formFieldValues).forEach((key) => {
        if (key === 'username' || key === 'password')
          formData.append(`additional_website_test_${key}`, formFieldValues[key]);
        else
          formData.append(
            `additional_website_${key}`,
            key === 'reason' ? formFieldValues[key] : autoPrefixUrls(formFieldValues[key], true),
          );
      });

      // If creds are not checked, removing these keys
      if (!doesNeedCreds) {
        formData.delete('additional_website_test_username');
        formData.delete('additional_website_test_password');
      }

      // remove these fields from getting submitted. Used in Revamped version of the flow
      formData.delete('additional_website_needsCreds');
      formData.delete('additional_website_type');

      if (file instanceof File) formData.append('additional_website_proof_url', file);
    } else {
      formData.append('additional_app_url', autoPrefixUrls(formFieldValues.app_url, true));
      formData.append('additional_app_reason', formFieldValues.reason);

      if (doesNeedCreds) {
        formData.append('additional_app_test_username', formFieldValues.username);
        formData.append('additional_app_test_password', formFieldValues.password);
      }
    }

    try {
      setIsSubmitting(true);
      const response = await merchantFetch({
        url: `merchant/additional_website/${type}`,
        method: 'POST',
        mode: 'live',
        data: formData,
        headers: {
          'Content-Type': 'application/json',
        },
      });

      if (response) {
        const { user } = props;
        props.showNotification({
          type: 'success',
          message: `${type} submitted successfully`,
        });
        if (props.flowType === FLOWS.ADDITIONAL_WEBSITE) {
          selfServeTrackSuccess({
            selfServeAction: 'Additional Website - App Url Updated',
            page: user?.isAccountAndSettingsRevampEnabled ? 'Business Website Details' : 'Profile',
            screen: user?.isAccountAndSettingsRevampEnabled ? 'Account & Settings' : 'My Account',
          });
        }

        props.fetchWorkflowStatus(WORKFLOW_TYPES.ADD_ADDITIONAL_WEBSITE);
        props.onDismiss?.();
        props.closeModal();
      }
    } catch ({ errors }) {
      setIsSubmitting(false);
      props.showNotification({
        type: 'error',
        message: errors,
      });
    }
  };

  const save = (e) => {
    e.preventDefault();

    const formFieldValues = Array.from(e.target.elements).reduce((acc, ele) => {
      acc[ele.name] = ele.value;
      return acc;
    }, {});

    delete formFieldValues.website;
    delete formFieldValues.app;
    delete formFieldValues[''];

    if (props.flowType === FLOWS.BUSINESS_WEBSITE) {
      submitBusinessDetails(formFieldValues);
    } else {
      submitAdditionalDomainDetails(formFieldValues);
    }
  };

  const onTypeCheckboxClick = (typeValue) => {
    settype(typeValue);
    setdoesNeedCreds(true);
    setfile(null);

    let analyticsObject;
    const { user } = props;

    // Edit flow
    if (user.has_key_access) {
      analyticsObject = {
        objectName: `Url type selected`,
        actionName: 'Toggled',
        screen: 'My account',
        properties: {
          flow: 'Website edit',
          urlType: `${typeValue}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    } else {
      // Add flow
      analyticsObject = {
        objectName: `Url type selected`,
        actionName: 'Toggled',
        screen: 'My account',
        properties: {
          isWebsiteUpdateV2: props.shouldShowV2,
          flow: 'Website add',
          urlType: `${typeValue}`,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      };
    }

    analyticsTrack(analyticsObject);
  };

  const onNeedsCredsClick = (value) => setdoesNeedCreds(value);

  const handleFileChange = (uploadedFile, _) => setfile(uploadedFile);

  const onCloseClick = () => setfile(null);

  const onBiggerFileSize = () => {
    const err = new Error('Document too large. Max limit 1MB', { cause: 'FILE_SIZE_EXCEEDED' });
    setfile(err);
  };

  const onTextInputBlur = (e) => {
    const input = e.target.value;
    const tokens = input.split(' ');

    if (tokens.length >= 50) setisReasonValid(true);
    else setisReasonValid(false);
  };

  const validateWebsiteNAppLink = (input) => {
    const value = isValidWebsite({ url: input });
    if (!value) {
      const errorMessage = 'Please enter valid url';
      setisLinkValid(false);
      setErrorMessages((prev) => ({ ...prev, url: errorMessage }));
      return errorMessage;
    } else {
      setisLinkValid(true);
    }
    if (props.flowType === FLOWS.ADDITIONAL_WEBSITE) {
      const { user } = props;

      // if additional website doesn't exits => Normal flow
      if (!user.additional_websites) {
        setErrorMessages((prev) => ({ ...prev, url: '' }));
        return '';
      } else {
        // Additional websites exists, check if new website already exists
        if (user.additional_websites.includes(input)) {
          const errorMessage = 'You have already added this website';
          setisLinkValid(false);
          setErrorMessages((prev) => ({ ...prev, url: errorMessage }));
          return errorMessage;
        }
        setisLinkValid(true);
        setErrorMessages((prev) => ({ ...prev, url: '' }));
        return '';
      }
    }
    setErrorMessages((prev) => ({ ...prev, url: '' }));
    return '';
  };

  const validateMetaUrls = (fieldName, input) => {
    const value = isValidWebsite({ url: input, isRazorpayDomainAllowed: true });
    if (!value) {
      const errorMessage = 'Please enter valid url';
      const _obj = { ...areMetaUrlsValid };
      _obj[fieldName] = false;
      setareMetaUrlsValid(_obj);
      setErrorMessages((prev) => ({ ...prev, [fieldName]: errorMessage }));
      return errorMessage;
    } else {
      const _obj = { ...areMetaUrlsValid };
      _obj[fieldName] = true;
      setareMetaUrlsValid(_obj);
    }
    setErrorMessages((prev) => ({ ...prev, [fieldName]: '' }));
    return '';
  };

  const areAllMetaLinksValid = () => {
    let areAllValid = true;
    Object.keys(areMetaUrlsValid).forEach((key) => {
      const value = areMetaUrlsValid[key];
      if (!value) areAllValid = false;
    });

    return areAllValid;
  };

  const isFormDisabled =
    isReasonValid === false || isLinkValid === false || areAllMetaLinksValid() === false;

  const appNote =
    'Make sure your app has about us, privacy policy, terms and conditions, refund policy pages. Your app will not be approved without these pages';

  return props.shouldShowV2 ? (
    <Modal
      isOpen={props.isOpen}
      onDismiss={props.onDismiss}
      size="medium"
      snapPoints={snapPoints}
      zIndex={99999}
    >
      <BladeModalHeader title="Update Website/App" />
      <ModalBody padding={isMobile ? 'spacing.5' : 'spacing.0'}>
        <form onSubmit={save}>
          <Box height={isMobile ? 'auto' : '550px'} overflowY="auto" display="flex">
            <Box
              paddingX={isMobile ? 'spacing.0' : 'spacing.8'}
              paddingTop={isMobile ? 'spacing.0' : 'spacing.7'}
              display="flex"
              flexDirection="column"
              flex="2"
              gap="spacing.8"
            >
              <Box display="flex" flexDirection="row" padding="spacing.1">
                <Box
                  display="flex"
                  flexDirection="column"
                  flex="2"
                  gap="spacing.6"
                  paddingBottom={isMobile ? 'spacing.5' : 'spacing.7'}
                >
                  <RadioGroup
                    label="Do you wish to integrate your website or app?"
                    value={type}
                    necessityIndicator="required"
                    isRequired
                    name="type"
                    onChange={({ value }) => onTypeCheckboxClick(value)}
                  >
                    <Radio value="website">Website</Radio>
                    <Radio value="app">App</Radio>
                  </RadioGroup>

                  <TextInput
                    label={type === 'website' ? 'Website URL' : 'App URL'}
                    name={type === 'website' ? 'main_page' : 'app_url'}
                    helpText="This should be the URL where you intend to collect payments"
                    necessityIndicator="required"
                    isRequired
                    onFocus={() => setErrorMessages((prev) => ({ ...prev, url: '' }))}
                    onBlur={({ value }) => validateWebsiteNAppLink(value)}
                    errorText={errorMessages.url}
                    validationState={
                      errorMessages.url ? ValidationState.ERROR : ValidationState.NONE
                    }
                  />

                  {props.flowType === FLOWS.ADDITIONAL_WEBSITE && (
                    <TextArea
                      placeholder={FORM_FIELDS.REASON.placeholder}
                      label={`Reason for adding new ${type}`}
                      necessityIndicator="required"
                      isRequired
                      name={FORM_FIELDS.REASON.name}
                      onFocus={() => setisReasonValid(true)}
                      onBlur={({ value }) => onTextInputBlur({ target: { value } })}
                      errorText={isReasonValid === false ? 'Minimum 50 words required' : ''}
                      validationState={
                        isReasonValid === false ? ValidationState.ERROR : ValidationState.NONE
                      }
                    />
                  )}

                  {type === 'website' && (
                    <WebsiteFields
                      handleFileChange={handleFileChange}
                      onCloseClick={onCloseClick}
                      flowType={props.flowType}
                      onBiggerFileSize={onBiggerFileSize}
                      file={file}
                      validator={validateMetaUrls}
                      shouldShowV2={props.shouldShowV2}
                      errorMessages={errorMessages}
                      setErrorMessages={setErrorMessages}
                    />
                  )}

                  <RadioGroup
                    label={`Does your ${type} require login from user to complete payment?`}
                    value={doesNeedCreds ? 'yes' : 'no'}
                    onChange={({ value }) => onNeedsCredsClick(value === 'yes')}
                    name="needsCreds"
                    necessityIndicator="required"
                    isRequired
                  >
                    <Radio value="yes">Yes</Radio>
                    <Radio value="no">No</Radio>
                  </RadioGroup>

                  {doesNeedCreds && (
                    <Box
                      backgroundColor="surface.background.gray.moderate"
                      padding="spacing.5"
                      borderRadius="medium"
                      gap="spacing.5"
                      display="flex"
                      flexDirection="column"
                    >
                      <TextInput
                        placeholder={FORM_FIELDS.USERNAME.placeholder}
                        label={FORM_FIELDS.USERNAME.label}
                        necessityIndicator="required"
                        isRequired
                        name={FORM_FIELDS.USERNAME.name}
                      />
                      <PasswordInput
                        placeholder={FORM_FIELDS.PASSWORD.placeholder}
                        label={FORM_FIELDS.PASSWORD.label}
                        necessityIndicator="required"
                        isRequired
                        name={FORM_FIELDS.PASSWORD.name}
                      />
                    </Box>
                  )}
                </Box>
              </Box>
            </Box>
            {isMobile ? null : (
              <Box
                padding="spacing.6"
                backgroundColor="surface.background.sea.subtle"
                flex="1.5"
                display="flex"
                justifyContent="center"
                height="auto"
                position="sticky"
                top="spacing.0"
              >
                <SuggestionsBox type="ADD_ADDITIONAL">
                  <Text weight="semibold">Mandatory details required on your {type} :</Text>
                  <Box display="flex" flexDirection="column" gap="spacing.2">
                    <Text weight="medium">Pricing Policy</Text>

                    <Text weight="medium">Shipping Policy</Text>
                    <Link
                      icon={ExternalLinkIcon}
                      iconPosition="right"
                      color="neutral"
                      href={FORM_FIELDS.TNC.href}
                      rel="noreferrer noopener"
                      target="_blank"
                    >
                      Terms and Conditions
                    </Link>
                    <Link
                      icon={ExternalLinkIcon}
                      iconPosition="right"
                      color="neutral"
                      href={FORM_FIELDS.PRIVACY_POLICY.href}
                      rel="noreferrer noopener"
                      target="_blank"
                    >
                      Privacy Policy
                    </Link>
                    <Link
                      icon={ExternalLinkIcon}
                      iconPosition="right"
                      color="neutral"
                      href={FORM_FIELDS.REFUND_POLICY.href}
                      rel="noreferrer noopener"
                      target="_blank"
                    >
                      Cancellation/Refund Policy
                    </Link>
                  </Box>
                </SuggestionsBox>
              </Box>
            )}
          </Box>
          <Box
            display="flex"
            gap="spacing.3"
            justifyContent="flex-end"
            width="100%"
            padding="spacing.6"
            borderTopColor="surface.border.gray.subtle"
          >
            {isMobile ? null : (
              <Button variant="tertiary" color="primary" size="medium" onClick={props.onDismiss}>
                Cancel
              </Button>
            )}
            <Button
              variant="primary"
              color="primary"
              size="medium"
              type="submit"
              form="additionalWebsiteForm"
              isDisabled={isFormDisabled}
              isLoading={isSubmitting}
            >
              Submit {type} for review
            </Button>
          </Box>
        </form>
      </ModalBody>
    </Modal>
  ) : (
    <div>
      <ModalHeader title="Update Website/App" onCloseClick={props.closeModal} />

      <div class="modal-body website-update-form">
        <form onSubmit={save}>
          <div class="help-block">
            Submit the url of your new website/app and the urls of all the other pages listed below
          </div>

          <div class="actions-header">
            <div>
              <input
                type="radio"
                class="radio-pointer"
                name="website"
                onChange={(e) => {
                  onTypeCheckboxClick(e.target.name, e.target.value);
                }}
                checked={type === 'website'}
              />
              <label>Website</label>
            </div>

            <div>
              <input
                type="radio"
                class="radio-pointer"
                name="app"
                onChange={(e) => {
                  onTypeCheckboxClick(e.target.name, e.target.value);
                }}
                checked={type === 'app'}
              />
              <label>App</label>
            </div>
          </div>

          <div class="form-group">
            <Input
              label={type === 'website' ? 'Website url' : 'App url'}
              autoFocus
              required
              name={type === 'website' ? 'main_page' : 'app_url'}
              validator={validateWebsiteNAppLink}
            />

            <hr />

            {type === 'website' && (
              <WebsiteFields
                handleFileChange={handleFileChange}
                onCloseClick={onCloseClick}
                flowType={props.flowType}
                onBiggerFileSize={onBiggerFileSize}
                file={file}
                validator={validateMetaUrls}
              />
            )}

            {props.flowType === FLOWS.ADDITIONAL_WEBSITE && (
              <>
                <Input.Textarea
                  placeholder={FORM_FIELDS.REASON.placeholder}
                  label={`Reason for adding new ${type}`}
                  required
                  name={FORM_FIELDS.REASON.name}
                  onBlur={onTextInputBlur}
                />
                {isReasonValid === false && (
                  <label style={{ color: '#f05050' }}>Minimum 50 words required</label>
                )}
              </>
            )}
          </div>

          <div class="form-group">
            <span class="info-container">
              <strong>Test account credentials</strong>
              <small class="help-content">
                <i class="i i-info-circle" />
                <Popover align="top" theme="dark" parentQuerySelector=".modal-body">
                  <PopoverBody>
                    <div>
                      Please provide credentials of a demo account in case your {type} requires the
                      user to create an account to transact. This will help us to login and verify
                      your {type}.
                    </div>
                  </PopoverBody>
                </Popover>
              </small>
            </span>
            {type === 'website' && (
              <span>
                <Input.Check
                  fieldLabel="My website doesn't require login to transact"
                  value={!doesNeedCreds}
                  onChange={(e) => {
                    const value = e.target.value === '1';
                    onNeedsCredsClick(!value);
                  }}
                />
              </span>
            )}

            {type === 'app' && (
              <span>
                <Input.Check
                  fieldLabel="My app doesn't require login to transact"
                  value={!doesNeedCreds}
                  onChange={(e) => {
                    const value = e.target.value === '1';
                    onNeedsCredsClick(!value);
                  }}
                />
              </span>
            )}

            {doesNeedCreds && (
              <>
                <Input
                  placeholder={FORM_FIELDS.USERNAME.placeholder}
                  name={FORM_FIELDS.USERNAME.name}
                  required
                />
                <Input
                  placeholder={FORM_FIELDS.PASSWORD.placeholder}
                  type="password"
                  name={FORM_FIELDS.PASSWORD.name}
                  required
                />
              </>
            )}

            {type === 'app' && <div class="note">{appNote}</div>}
          </div>

          <div class="Modal__actions">
            <button type="submit" class="btn btn-primary btn-block" disabled={isFormDisabled}>
              Submit {type} for review
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      ...NotificationsActions,
      fetchWorkflowStatus: fetchWorkflowStatusReducer,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(UpdateWebsiteDetails);
