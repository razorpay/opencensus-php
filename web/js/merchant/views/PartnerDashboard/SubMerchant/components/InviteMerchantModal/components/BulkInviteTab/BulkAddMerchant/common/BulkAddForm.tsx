import React from 'react';
import { Box, Button } from '@razorpay/blade/components';
import { FormikValues, useFormik } from 'formik';
import { isEmpty } from 'lodash';
import * as Yup from 'yup';

import CheckRound from 'assets/check-round.svg';
import { CommonApiResponse, FormikHandleChange } from 'common/typings';
import ModalFooter from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/ModalCommon/ModalFooter';

import { StyledBulkAddForm } from './styled';
import BatchValidate from './BatchValidateTyped';

const validationSchema = Yup.object().shape({
  file_id: Yup.string()
    .required('Please upload a document (.csv or .xlsx) here to proceed ahead')
    .nullable(),
  processable_count: Yup.number().nullable(),
});

type BulkAddFormProps = {
  batchType: string;
  clickToUploadAnalytics: () => void;
  handleFormSubmit: (params: FormikValues) => void;
  isSendingInvites: boolean;
  onBackClick: () => void;
  onValidationFail: (error: Error) => void;
  onValidationSuccess: () => void;
  sampleFileDownloadAnalytics: () => void;
  sampleUrl: string;
  showBackButton?: boolean;
  validateBatch: () => Promise<CommonApiResponse<{ status: boolean }, string[]>>;
};
const BulkAddForm = ({
  batchType,
  clickToUploadAnalytics,
  handleFormSubmit,
  isSendingInvites,
  onBackClick,
  onValidationFail,
  onValidationSuccess,
  sampleFileDownloadAnalytics,
  sampleUrl,
  validateBatch,
  showBackButton = false,
}: BulkAddFormProps): JSX.Element => {
  const initialValues = {
    file_id: '',
    processable_count: 0,
  };

  const formik = useFormik<FormikValues>({
    initialValues,
    validationSchema,
    validateOnChange: true,
    validateOnBlur: false,
    onSubmit: handleFormSubmit,
  });

  const onSendInvitesClick = async () => {
    const errors = await formik.validateForm();

    if (!isEmpty(errors)) {
      return;
    }
    formik.handleSubmit();
  };

  const handleChange: FormikHandleChange = ({ name, value }) => {
    if (name) {
      formik.setFieldTouched(name);
      formik.setFieldValue(name, value);
    }
  };
  const { file_id: fileId, processable_count: bulkContactsCount = 0 } = formik.values;

  const onValidation = (response, _name) => {
    if (response && response.file_id) {
      handleChange({ name: 'file_id', value: response.file_id });
      handleChange({ name: 'processable_count', value: Number(response.processable_count) });
    }
    onValidationSuccess();
  };
  const onFileRemove = () => {
    handleChange({ name: 'file_id', value: '' });
    handleChange({ name: 'processable_count', value: 0 });
  };
  return (
    <StyledBulkAddForm>
      <Box display="flex" gap="spacing.5" alignItems="center">
        {/* Note: these classnames are needed for inner styling of the old BatchValidate component */}
        <div className="partner-submerchant-modal">
          <div className="modal-body">
            <BatchValidate
              batchType={batchType}
              batchTypeText="text"
              clickToUploadAnalytics={clickToUploadAnalytics}
              onValidation={onValidation}
              onFileRemove={onFileRemove}
              sampleFileDownloadAnalytics={sampleFileDownloadAnalytics}
              sampleUrl={sampleUrl}
              validateBatch={validateBatch}
              maxRows={500}
              maxFileSize={52428800}
              batchClass="batch-upload-modal"
              onValidationFail={onValidationFail}
            />
            {fileId ? (
              <Box display="flex" flexDirection="column" gap="spacing.0">
                <div className="success-message flex-col-between">
                  <div>
                    <p>
                      <img src={CheckRound} alt="Tick icon" /> &nbsp;
                      {bulkContactsCount} contacts have been identified.
                    </p>
                    <span>
                      Email will be sent to {bulkContactsCount} identified contacts. Status of the
                      invite will be sent to your email address within 2 hours.
                    </span>
                  </div>
                </div>
              </Box>
            ) : null}
          </div>
        </div>
      </Box>

      <ModalFooter>
        {showBackButton ? (
          <Button variant="tertiary" onClick={onBackClick}>
            Back
          </Button>
        ) : null}
        <Button
          isLoading={isSendingInvites}
          isDisabled={isSendingInvites || !bulkContactsCount}
          onClick={onSendInvitesClick}
        >
          Invite {bulkContactsCount} contact
          {bulkContactsCount === 1 ? '' : 's'}
        </Button>
      </ModalFooter>
    </StyledBulkAddForm>
  );
};

export default BulkAddForm;
