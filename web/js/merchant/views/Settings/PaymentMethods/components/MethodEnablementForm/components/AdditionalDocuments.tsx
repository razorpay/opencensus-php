import React from 'react';
import {
  Box,
  Text,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Heading,
  InfoIcon,
  Checkbox,
  Tooltip,
  Button,
  TooltipInteractiveWrapper,
  DownloadIcon,
  Link,
} from '@razorpay/blade/components';
import { useFormikContext } from 'formik';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import {
  FORMIK_FORM_KEYS,
  SAMPLE_UBO_FILE,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import { useAdditionalDocuments } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/hooks/useAdditionalDocuments';
import useFormContext from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/hooks/useFormContext';
import { saveAdditionalDocumentFormData } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/services';
import { StyledFieldContainer } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/styles';
import {
  AdditionalDocumentsProps,
  ApiDataType,
  FileDropBoxProps,
  FormikValues,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/types';
import { showNotification } from 'merchant_common/reducers/notifications';

const AdditionalDocuments = ({ user, showNotification }: AdditionalDocumentsProps) => {
  const { setFieldValue, values } = useFormikContext<FormikValues>();
  const { onTabClick, apiData } = useFormContext();
  const {
    docs,
    selectedDocument,
    formikDocuments,
    selectedOption,
    handleSelect,
    handleFileRemove,
    handleUploadFile,
    filterOtherSelectOptions,
  } = useAdditionalDocuments({
    user,
    saveFormData: (formData) => {
      saveAdditionalDocumentFormData(apiData as ApiDataType, formData.values, user);
    },
    showNotification,
  });

  const onCheckboxClick = (e) => {
    const { isChecked, value } = e;
    setFieldValue(value, isChecked);
  };

  const onHowToEsign = () => onTabClick(0);

  const onDownloadSampleUbo = () => window.open(SAMPLE_UBO_FILE, '_blank');

  const FileDropBox = ({ doc, index }: FileDropBoxProps) => {
    if (doc.type === 'select') {
      return (
        <Box maxWidth="560px" marginBottom="spacing.7">
          <Box marginBottom="spacing.7">
            <Dropdown>
              <SelectInput
                label={doc.label}
                name={doc.name}
                placeholder="Select Document Type"
                value={selectedOption[index]}
                helpText="These are additional documents required as per RBI’s KYC guidelines"
                necessityIndicator="required"
                onChange={({ values }) => handleSelect(values[0], index)}
              />
              <DropdownOverlay>
                <ActionList>
                  {filterOtherSelectOptions(index, doc.options).map(({ label, name }) => (
                    <ActionListItem key={label} title={label} value={name} />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          </Box>
          {selectedDocument[index] ? (
            <Box>
              <Box display="flex" flexDirection="row" alignItems="center" paddingBottom="spacing.3">
                <Text weight="bold" marginRight="spacing.2" type="subdued">
                  {selectedDocument[index].label}
                </Text>
                {selectedDocument[index].tooltip && (
                  <Tooltip
                    zIndex={100000}
                    content={selectedDocument[index].tooltip ?? ''}
                    placement="top"
                  >
                    <TooltipInteractiveWrapper>
                      <InfoIcon color="surface.action.icon.active.lowContrast" size="medium" />
                    </TooltipInteractiveWrapper>
                  </Tooltip>
                )}
              </Box>
              <StyledFieldContainer>
                <Input.File
                  key={selectedDocument[index].name}
                  name={selectedDocument[index].name}
                  required
                  _accept={['jpg', 'jpeg', 'pdf']}
                  maxSize={10485760} //10 MB
                  defaultValue={formikDocuments[selectedDocument[index].name]?.[0]?.display_name}
                  fileName={formikDocuments[selectedDocument[index].name]?.[0]?.display_name}
                  onChange={(file, progressTracker) =>
                    handleUploadFile(selectedDocument[index].name, file, progressTracker)
                  }
                  onCloseClick={(docId) => handleFileRemove(docId, selectedDocument[index].name)}
                />
              </StyledFieldContainer>
              <Box marginTop="spacing.2">
                <Text type="muted" as="span" size="xsmall">
                  This document needs to be e-signed/self-attested.{' '}
                </Text>
                <Link variant="button" onClick={onHowToEsign} size="xsmall">
                  How do I e-sign or self-attest a document?
                </Link>
              </Box>
            </Box>
          ) : null}
        </Box>
      );
    }
    return (
      <Box maxWidth="560px" marginBottom="spacing.7">
        <Box>
          <Box display="flex" flexDirection="row" alignItems="center" paddingBottom="spacing.3">
            <Text weight="bold" marginRight="spacing.2" type="subdued">
              {doc.label}
            </Text>
            {doc.tooltip && (
              <Tooltip content={doc.tooltip} placement="top">
                <TooltipInteractiveWrapper>
                  <InfoIcon color="surface.action.icon.active.lowContrast" size="medium" />
                </TooltipInteractiveWrapper>
              </Tooltip>
            )}
            {doc.name === 'ubo' && (
              <Button
                icon={DownloadIcon}
                iconPosition="right"
                isFullWidth={false}
                marginRight="spacing.0"
                marginLeft="auto"
                display={{ base: 'none', m: 'block' }}
                size="small"
                onClick={onDownloadSampleUbo}
              >
                Download Sample UBO
              </Button>
            )}
          </Box>
          <StyledFieldContainer>
            <Input.File
              key={doc.name}
              name={doc.name}
              required
              _accept={['jpg', 'jpeg', 'pdf']}
              maxSize={10485760} //10 MB
              defaultValue={formikDocuments[doc.name]?.[0]?.display_name}
              fileName={formikDocuments[doc.name]?.[0]?.display_name}
              onChange={(file, progressTracker) =>
                handleUploadFile(doc.name, file, progressTracker)
              }
              onCloseClick={() =>
                handleFileRemove(formikDocuments[doc.name]?.[0]?.id as string, doc.name)
              }
            />
          </StyledFieldContainer>
          {doc.name === 'ubo' ? (
            <Box marginTop="spacing.2">
              <Text type="muted" as="span" size="xsmall">
                Please{' '}
              </Text>
              <Link variant="button" onClick={onDownloadSampleUbo} size="xsmall">
                download sample UBO,{' '}
              </Link>
              <Text type="muted" as="span" size="xsmall">
                insert organization letterhead and fill in the details before uploading it above
              </Text>
            </Box>
          ) : (
            <Box marginTop="spacing.2">
              <Text type="muted" as="span" size="xsmall">
                This document needs to be e-signed/self-attested.{' '}
              </Text>
              <Link variant="button" onClick={onHowToEsign} size="xsmall">
                How do I e-sign or self-attest a document?
              </Link>
            </Box>
          )}
          {doc.name === 'ubo' && (
            <Button
              icon={DownloadIcon}
              iconPosition="right"
              isFullWidth={false}
              display={{ base: 'block', m: 'none' }}
              size="small"
              marginTop="spacing.2"
            >
              Download Sample UBO
            </Button>
          )}
        </Box>
      </Box>
    );
  };

  return (
    <Box>
      <Heading size="medium" marginBottom="spacing.8">
        KYC Documents
      </Heading>
      <Box>
        {docs?.map((doc, index) => (
          <FileDropBox key={doc.name} doc={doc} index={index} />
        ))}
        <Box
          paddingTop="spacing.7"
          marginTop="spacing.8"
          borderTopColor="surface.border.normal.lowContrast"
        >
          <Checkbox
            value={FORMIK_FORM_KEYS.KYC_TNC_ACCEPTED}
            defaultChecked={values[FORMIK_FORM_KEYS.KYC_TNC_ACCEPTED]}
            onChange={onCheckboxClick}
          >
            I hereby confirm that all above documents have been self-attested/digitally signed by
            issuing authority
          </Checkbox>
        </Box>
      </Box>
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  showNotification,
})(AdditionalDocuments);
