import React, { useEffect, useState } from 'react';
import {
  Box,
  Text,
  Modal,
  ModalBody,
  DatePicker,
  ModalHeader,
  Divider,
  Button,
  ModalFooter,
  TextInput,
  Alert,
  TextArea,
  Spinner,
  InfoIcon,
  Tooltip,
  CheckCircleIcon,
} from '@razorpay/blade/components';
import isEmpty from 'lodash/isEmpty';
import moment from 'moment/moment';

import { toTitleCase } from 'common/utils';
import { getWebsiteDetailsInfo } from 'merchant/views/Settings/Configuration/Questionnaire/utils';

import {
  countUnverifiedWebsiteDetails,
  getGroupedCollectInfo,
  groupByVerificationStatus,
  hasValidFieldsForCategory,
  isFieldValidUrl,
  isWebsiteDetailsPresent,
  isBusinessDetailsPresent,
} from './utils';
import { DETAILS_TYPE, FIELD_STATUS } from 'merchant/views/Settings/PaymentMethods/constants';

const WebsiteDetailsModal = ({
  props,
  isOpen,
  retries,
  collectInfo,
  handleClose,
  submitLoading,
  handleRetryClick,
  handleSubmitRequest,
  handleWebsiteFieldChange,
}) => {
  const [isAdditionalDetailsView, setAdditionalDetailsView] = useState(false);
  const merchantWebsiteDetails = getWebsiteDetailsInfo(props.user);
  const [initialStateOfFields, setInitialStateOfField] = useState([]);
  const [allPagesVerified, setAllPagesVerified] = useState(false);

  useEffect(() => {
    const groupedCollectInfo = getGroupedCollectInfo(collectInfo);
    const keys = Object.keys(groupedCollectInfo);
    if (isWebsiteDetailsPresent(collectInfo)) {
      const groupedByVerficiationStatus = groupByVerificationStatus(
        groupedCollectInfo[DETAILS_TYPE.website],
      );
      setAllPagesVerified(
        Object.keys(groupedByVerficiationStatus).join('') === FIELD_STATUS.verified,
      );
    }

    if (!isWebsiteDetailsPresent(collectInfo) && keys.length !== 0) {
      setAdditionalDetailsView(true);
    }
    const initial = collectInfo
      .map((item) => {
        if (item.category === DETAILS_TYPE.website && !item?.verification_status) {
          return {
            name: item.name,
            value: item.value || '',
          };
        }
        return undefined;
      })
      .filter((el) => el !== undefined);
    setInitialStateOfField(initial);
  }, []);

  const handleFieldChange = ({ name, value }) => {
    handleWebsiteFieldChange(name, value);
  };

  const validateWebsiteFields = () => {
    let flag = true;
    for (let idx = 0; idx < collectInfo.length; idx++) {
      const item = collectInfo[idx];
      if (item.category === DETAILS_TYPE.website && !item?.verification_status) {
        const resp = isFieldValidUrl(
          item.value,
          merchantWebsiteDetails.isWebsiteDetails
            ? merchantWebsiteDetails.websitesData[0]
            : undefined,
        );

        if (!resp) {
          flag = false;
          break;
        }
      }
    }
    return !flag;
  };

  const isRetryNeeded = () => {
    if (!isWebsiteDetailsPresent(collectInfo)) return false;
    if (retries < 2) {
      return true;
    }
    return false;
  };

  const isValidDetailsFilled = (category) => {
    return !hasValidFieldsForCategory(collectInfo, category);
  };

  const handleNext = () => {
    if (isAdditionalDetailsView || !isBusinessDetailsPresent(collectInfo)) {
      handleSubmitRequest();
    } else {
      setAdditionalDetailsView(true);
    }
  };

  const renderWebsiteFields = (websiteDetails, type) => {
    const groupedWebsiteDetails = groupByVerificationStatus(websiteDetails);

    if (!groupedWebsiteDetails) {
      return <Text>Something went wrong</Text>;
    }

    return (
      <>
        {(allPagesVerified || initialStateOfFields.length) && groupedWebsiteDetails[type]
          ? groupedWebsiteDetails[type].map((item, idx) => {
              return (
                <Box key={item.name} display="flex" alignItems="center" gap="spacing.4">
                  <Box width="376px">
                    <TextInput
                      label={item.readableName}
                      labelPosition="left"
                      name={item.name}
                      value={item.value || ''}
                      validationState={
                        !item.value ? 'error' : type === FIELD_STATUS.verified ? 'none' : 'error'
                      }
                      isDisabled={type === FIELD_STATUS.verified}
                      onChange={handleFieldChange}
                    />
                  </Box>
                  {type !== FIELD_STATUS.verified ? (
                    <Tooltip
                      content={
                        !initialStateOfFields[idx].value
                          ? 'Page not found. Please add this page to your website or provide the correct URL.'
                          : "Page found but doesn't meet requirements. Please update the content according to guidelines."
                      }
                      placement="right"
                    >
                      <Box>
                        <InfoIcon size="xlarge" color="feedback.icon.notice.intense" />
                      </Box>
                    </Tooltip>
                  ) : (
                    <Box>
                      <CheckCircleIcon size="xlarge" color="feedback.icon.positive.intense" />
                    </Box>
                  )}
                </Box>
              );
            })
          : null}
      </>
    );
  };

  const renderWebsiteDetails = (websiteDetails) => {
    if (!websiteDetails[DETAILS_TYPE.website]) {
      return <Text>Something went wrong! Please try again</Text>;
    }
    const websiteFieldLength = websiteDetails[DETAILS_TYPE.website].length;
    const unverifiedFieldLength = countUnverifiedWebsiteDetails(
      websiteDetails[DETAILS_TYPE.website],
    );
    return (
      <>
        {unverifiedFieldLength !== 0 ? (
          <>
            <Alert
              isDismissible={false}
              color="notice"
              description={
                <>
                  Verification was incomplete. Hover over items marked with{' '}
                  <InfoIcon size="small" color="feedback.icon.notice.intense" /> for specific
                  requirements and next steps.
                </>
              }
              isFullWidth
            />
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Text variant="body" weight="semibold" size="medium">
                Needs Clarification
              </Text>
              {renderWebsiteFields(websiteDetails[DETAILS_TYPE.website], FIELD_STATUS.missing)}
            </Box>
          </>
        ) : null}
        {websiteFieldLength - unverifiedFieldLength !== 0 ? (
          <>
            {unverifiedFieldLength !== 0 ? <Divider /> : null}
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Text variant="body" weight="semibold" size="medium">
                {unverifiedFieldLength === 0
                  ? 'All policy pages verified successfully'
                  : FIELD_STATUS.verified}
              </Text>
              {renderWebsiteFields(websiteDetails[DETAILS_TYPE.website], FIELD_STATUS.verified)}
            </Box>
          </>
        ) : null}
        {!allPagesVerified && !isAdditionalDetailsView && !isRetryNeeded() ? (
          <Alert
            description={`Important: Due to incomplete website verification, your ${props.instrument.name} activation request may be rejected by the banking partner. We recommend ensuring all pages exist and meet guidelines before proceeding`}
            isDismissible={false}
            color="negative"
            isFullWidth
          />
        ) : null}
      </>
    );
  };

  const renderGeneralInfo = (fields) => {
    return (
      <Box display="flex" flexDirection="column" gap="spacing.4">
        {fields.map(({ type, display_name, placeholder, name }, i) => {
          display_name = toTitleCase(display_name);
          switch (type) {
            case 'text':
              return (
                <Box width="376px">
                  <TextInput
                    key={i}
                    label={display_name}
                    labelPosition="left"
                    placeholder={placeholder}
                    onChange={handleFieldChange}
                    name={name}
                  />
                </Box>
              );
            case 'textarea':
              return (
                <Box width="376px">
                  <TextArea
                    key={i}
                    label={display_name}
                    labelPosition="left"
                    placeholder={placeholder}
                    size="large"
                    numberOfLines={3}
                    onChange={handleFieldChange}
                    name={name}
                  />
                </Box>
              );
            case 'number':
              return (
                <Box width="376px">
                  <TextInput
                    key={i}
                    label={display_name}
                    labelPosition="left"
                    type="number"
                    placeholder={placeholder}
                    onChange={handleFieldChange}
                    name={name}
                  />
                </Box>
              );
            case 'date':
              return (
                <Box width="376px">
                  <DatePicker
                    key={i}
                    label={display_name}
                    labelPosition="left"
                    onChange={(date) =>
                      handleFieldChange({ name, value: moment(date).format('YYYY-MM-DD') })
                    }
                    name={name}
                  />
                </Box>
              );
            default:
              return (
                <Box width="376px">
                  <TextInput
                    key={i}
                    label={display_name}
                    labelPosition="left"
                    placeholder={placeholder}
                    onChange={handleFieldChange}
                    name={name}
                  />
                </Box>
              );
          }
        })}
      </Box>
    );
  };

  const renderAdditionalDetails = (websiteDetails) => {
    const categories = Object.keys(websiteDetails);
    return (
      <Box display="flex" flexDirection="column" gap="spacing.4">
        {categories.map((category) => {
          if (category === DETAILS_TYPE.website) {
            return null;
          }
          return renderGeneralInfo(websiteDetails[category]);
        })}
      </Box>
    );
  };

  const renderModalBody = () => {
    if (!collectInfo || isEmpty(collectInfo)) return <Spinner />;
    const categories = getGroupedCollectInfo(collectInfo);

    return (
      <Box display="flex" flexDirection="column" gap="spacing.7">
        {isAdditionalDetailsView
          ? renderAdditionalDetails(categories)
          : renderWebsiteDetails(categories)}
      </Box>
    );
  };

  const renderModalHeader = () => {
    return !isAdditionalDetailsView
      ? `Page Compliance Check - ${
          merchantWebsiteDetails.isWebsiteDetails
            ? merchantWebsiteDetails.websitesData[0]
            : "for Merchant's Website"
        }`
      : 'Additional Details Required';
  };

  const renderModalFooter = () => {
    if (allPagesVerified && !isAdditionalDetailsView) {
      return (
        <Button variant={'primary'} isLoading={submitLoading} onClick={handleNext}>
          {!isBusinessDetailsPresent(collectInfo) ? 'Submit Request' : 'Proceed'}
        </Button>
      );
    }

    if (allPagesVerified && isAdditionalDetailsView) {
      return (
        <Button
          variant="primary"
          onClick={handleNext}
          isLoading={submitLoading}
          isDisabled={isValidDetailsFilled(DETAILS_TYPE.business)}
        >
          Submit Request
        </Button>
      );
    }

    return isRetryNeeded() ? (
      <Button onClick={handleRetryClick} isDisabled={validateWebsiteFields()}>
        Verify Again
      </Button>
    ) : (
      <>
        <Button
          variant={!isAdditionalDetailsView ? 'secondary' : 'primary'}
          onClick={handleNext}
          isLoading={submitLoading}
          isDisabled={
            !isAdditionalDetailsView
              ? validateWebsiteFields()
              : isValidDetailsFilled(DETAILS_TYPE.business)
          }
        >
          {!isAdditionalDetailsView ? 'Proceed Anyway' : 'Submit Request'}
        </Button>
        {!isAdditionalDetailsView ? (
          <Button variant="primary" onClick={handleRetryClick} isDisabled={validateWebsiteFields()}>
            Verify Again
          </Button>
        ) : null}
      </>
    );
  };

  return (
    <Modal isOpen={isOpen} onDismiss={handleClose} size="medium">
      <ModalHeader title={renderModalHeader()} />
      <ModalBody>{renderModalBody()}</ModalBody>
      <ModalFooter>
        <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
          {renderModalFooter()}
        </Box>
      </ModalFooter>
    </Modal>
  );
};
export default WebsiteDetailsModal;
