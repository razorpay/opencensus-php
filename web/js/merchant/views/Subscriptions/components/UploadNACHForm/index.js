import React from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import PropTypes from 'prop-types';
import { findBy, normalizeDate, classList } from 'common/utils/rzp-utils';

import { showNotification } from 'merchant_common/reducers/notifications';
import { validateNachFile, authenticateNACHFile } from 'merchant/reducers/registration_link';

import Accordion, {
  AccordionItem,
  AccordionItemTitle,
  AccordionItemContent,
} from 'common/ui/Accordion';

import Alert from 'common/ui/Forms/Alert';
import Amount from 'common/ui/Amount';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import FileUpload from 'merchant/components/File/Upload';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import {
  trackUploadNachFormStatus,
  trackReadNachFormStatus,
  trackClickMandateDetails,
  trackClickPersonalDetails,
  trackClickBankDetails,
} from './ga';

const MandateFields = ['amount', 'frequency', 'debit_type'];

const PersonalDetailsFields = ['customer.name', 'customer.contact', 'customer.email'];

const BankAccountFields = [
  'bank_account.account_number',
  'bank_account.ifsc_code',
  'bank_account.account_type',
];

const initState = {
  uploading: false,
  extractedData: {
    id: null,
    errors: {
      not_matching: [],
    },
    extracted_data: [],
    enhanced_image: null,
  },
  file: null,
  errors: {
    heading: '',
    description: '',
  },
};

@connect(null, {
  showNotification,
})
@RTracking(() => window.rzpQ.component('UploadNACHForm'))
export default class UploadNACHForm extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);

    this.state = initState;
  }

  get errorsList() {
    if (!this.state.extractedData.errors) {
      return [];
    }

    return this.state.extractedData.errors.not_matching;
  }

  get mandateStatus() {
    const isError = MandateFields.some((field) => this.errorsList.includes(field));

    if (isError) {
      return 'danger';
    }

    return 'primary';
  }

  get personalDetailsStatus() {
    const isError = PersonalDetailsFields.some((field) => this.errorsList.includes(field));

    if (isError) {
      return 'danger';
    }

    return 'primary';
  }

  get bankAccountStatus() {
    const isError = BankAccountFields.some((field) => this.errorsList.includes(field));

    if (isError) {
      return 'danger';
    }

    return 'primary';
  }

  componentDidMount() {
    this.trackNACHUpload('initiated');
  }

  trackNACHUpload = (event, options) => {
    if (!event) return;

    this.props.tracking.trackEvent(
      window.rzpQ.chargeAtWill().interaction(`nach_upload.${event}`, options),
    );
  };

  getDataFromExtractedData = (key) => {
    const data = findBy(this.state.extractedData.extracted_data, 'key', key);

    return {
      isError: this.errorsList.includes(key),
      value: data.extracted_value,
    };
  };

  onCloseClick = () => {
    this.trackNACHUpload('remove_file.initiate');

    this.context.confirm({
      header: 'Remove Nach Form',
      message:
        'The attached NACH form will be discarded and you will need to re-upload a new image.',
      affirmativeLabel: 'Yes, Remove',
      action: () => {
        this.setState(initState);

        this.trackNACHUpload('remove_file.success');
      },
      abort: () => {
        this.trackNACHUpload('remove_file.abort');
      },
    });
  };

  handleChange = (file) => {
    this.setState({
      uploading: true,
      file,
    });

    this.trackNACHUpload('file.validating.initiate');

    return validateNachFile(file, this.props.id)
      .then((resp) => {
        this.setState(
          {
            extractedData: resp.data,
            uploading: false,
          },
          () => {
            trackClickMandateDetails(this.mandateStatus === 'danger');
            trackClickPersonalDetails(this.personalDetailsStatus === 'danger');
            trackClickBankDetails(this.bankAccountStatus === 'danger');
          },
        );

        trackReadNachFormStatus('success');

        this.trackNACHUpload('file.validating.success');
      })
      .catch((error) => {
        trackReadNachFormStatus('error', error.errors[0]);

        this.setState({
          uploading: false,
          errors: getErrorMessage(error.errors),
        });

        this.trackNACHUpload('file.validating.fail', {
          response: getErrorMessage(error.errors),
        });
      });
  };

  handleSubmit = () => {
    this.trackNACHUpload('file.submit.initiate');

    return authenticateNACHFile(this.state.extractedData.id, this.props.id)
      .then((resp) => {
        this.setState({
          extractedData: resp.data,
        });

        trackUploadNachFormStatus('success');

        this.trackNACHUpload('file.submit.success');

        this.props.showNotification({
          type: 'success',
          message: 'NACH form uploaded successfully',
        });

        if (this.props.onClose) {
          this.props.onClose();
        } else {
          const redirectUrl = `/registration_links/${this.props.id}`;

          this.props.history.push(redirectUrl);
        }
      })
      .catch((err) => {
        trackUploadNachFormStatus('error', err.errors[0]);

        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.trackNACHUpload('file.submit.fail', { response: err.errors });
      });
  };

  renderDesc = () => {
    const { uploading, errors } = this.state;

    if (uploading) {
      return (
        <>
          <i class="i i-info-circle" /> Please wait while we upload NACH form.
        </>
      );
    }

    if (this.errorsList.length) {
      return (
        <>
          <h5 class="text-danger">
            <i class="i i-info-circle" /> Details do not match
          </h5>
          <p>
            The highlighted details on the uploaded NACH form do not match. Please ensure you are
            uploading the correct NACH form.
          </p>
        </>
      );
    }

    if (errors.heading) {
      return (
        <>
          <h5 class="text-danger">
            <i class="i i-info-circle" /> {errors.heading}
          </h5>

          {errors.description && <p class="description">{errors.description}</p>}

          {errors.hint && (
            <Alert class="hint" type="warning" message={errors.hint} showDismiss={false} />
          )}
        </>
      );
    }
    return '';
  };

  renderNachFieldData = (key) => {
    const { isError, value } = this.getDataFromExtractedData(key);

    const child = key === 'amount' ? <Amount value={value} /> : value;

    return <div class={classList(isError && 'text-danger')}>{child}</div>;
  };

  renderNachDetails = () => {
    if (!this.state.extractedData.extracted_data.length || this.state.uploading) {
      return '';
    }

    let endAt = this.renderNachFieldData('end_at');
    const startAt = this.renderNachFieldData('start_at');

    endAt = endAt && normalizeDate(endAt);

    return (
      <Accordion>
        <AccordionItem status={this.mandateStatus}>
          <AccordionItemTitle>Mandate Details</AccordionItemTitle>
          <AccordionItemContent>
            <EntityDetailRow label="Amount">{this.renderNachFieldData('amount')}</EntityDetailRow>
            <EntityDetailRow label="Frequency">
              {this.renderNachFieldData('frequency')}
            </EntityDetailRow>
            <EntityDetailRow label="Debit Type">
              {this.renderNachFieldData('debit_type')}
            </EntityDetailRow>
            <EntityDetailRow label="Debit from">{startAt}</EntityDetailRow>
            <EntityDetailRow label="Debit To">{endAt ? endAt : 'Until cancelled'}</EntityDetailRow>
          </AccordionItemContent>
        </AccordionItem>
        <AccordionItem status={this.personalDetailsStatus}>
          <AccordionItemTitle>Customer&#39;s Personal Details</AccordionItemTitle>
          <AccordionItemContent>
            <EntityDetailRow label="Name">
              {this.renderNachFieldData('customer.name')}
            </EntityDetailRow>
            <EntityDetailRow label="Phone no">
              {this.renderNachFieldData('customer.contact')}
            </EntityDetailRow>
            <EntityDetailRow label="Email ID">
              {this.renderNachFieldData('customer.email')}
            </EntityDetailRow>
          </AccordionItemContent>
        </AccordionItem>
        <AccordionItem status={this.bankAccountStatus}>
          <AccordionItemTitle>Customer&#39;s Bank Details</AccordionItemTitle>
          <AccordionItemContent>
            <EntityDetailRow label="Account no">
              {this.renderNachFieldData('bank_account.account_number')}
            </EntityDetailRow>
            <EntityDetailRow label="IFSC code">
              {this.renderNachFieldData('bank_account.ifsc_code')}
            </EntityDetailRow>
            <EntityDetailRow label="Account Type">
              {this.renderNachFieldData('bank_account.account_type')}
            </EntityDetailRow>
          </AccordionItemContent>
        </AccordionItem>
      </Accordion>
    );
  };

  onClose = () => {
    this.trackNACHUpload('close');

    this.props.onClose();
  };

  render() {
    const { uploading, file, errors } = this.state;
    const isDataAval = this.state.extractedData.extracted_data.length || this.state.errors.heading;
    const disabled =
      uploading || !!this.errorsList.length || !this.state.extractedData.extracted_data.length;

    const isModalView = this.props.onClose;

    const contentView = (
      <div
        class={classList(
          'ModalSingleForm',
          'Wizard',
          'UploadNACH',
          !isDataAval && 'UploadNACH--Form',
        )}
      >
        <main>
          <main-title class="main-title">Upload NACH Form</main-title>

          <p class="file-desc">
            If you received the customer&#39;s signed NACH form, you can upload it here.
          </p>

          <FileUpload
            showCloseBtn
            showFileSize
            showStagedFileStatus
            stagedFileStatus="error"
            maxSize="5242880"
            accept={['png', 'jpg', 'jpeg']}
            size="large"
            files={file ? [file] : []}
            uploadedFileName="Upload File here"
            onFileChange={this.handleChange}
            onCloseClick={this.onCloseClick}
            dropZoneCavityClassName={classList(
              `Dropzone-cavity--${
                uploading
                  ? 'process'
                  : this.errorsList.length || errors.heading
                  ? 'error'
                  : 'success'
              }`,
            )}
          />

          <div class="Desc">{this.renderDesc()}</div>

          <div class="Details">{this.renderNachDetails()}</div>
        </main>

        <footer>
          {isModalView && <Button onClick={this.onClose}>Cancel</Button>}

          <AsyncBtn.Primary
            pendingState="Uploading..."
            onClick={this.handleSubmit}
            disabled={disabled}
          >
            Upload NACH
          </AsyncBtn.Primary>
        </footer>
      </div>
    );

    if (!isModalView) {
      return <div class="StandAloneContainer">{contentView}</div>;
    }

    return (
      <Modal class={classList('UploadNACHForm animate-down')} onClose={this.onClose}>
        <ModalContent>{contentView}</ModalContent>
      </Modal>
    );
  }
}

function getErrorMessage([error, status]) {
  if (status === 500) {
    return {
      heading: 'Apologies, an error occurred on our end',
      description:
        'We are experiencing an internal server issue. Please retry uploading the NACH form after sometime.',
    };
  }

  if (error.includes('unable to read')) {
    return {
      heading: 'NACH form could not be read',
      description:
        'Kindly re-upload an image with better quality as the uploaded form could not be read successfully.',
      hint: (
        <>
          The uploaded image should be <b>clear</b>. It should not be <b>cropped</b> and not have
          any <b>shadows</b>.
        </>
      ),
    };
  } else if (error.includes('signature is not detected in the NACH form')) {
    return {
      heading: 'Signature is not visible',
      description:
        'Kindly upload an image with better quality and ensure that the form has been signed.',
    };
  }

  return {
    heading: error,
  };
}
