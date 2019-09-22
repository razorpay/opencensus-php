import { connect } from 'react-redux';
import { classList } from 'common/util';
import { findBy, normalizeDate } from 'rzp/utils/rzp-utils';

import { showNotification } from 'rzp/modules/notifications';
import {
  validateNachFile,
  authenticateNACHFile,
} from 'merchant/modules/registration_link';

import Accordion, {
  AccordionItem,
  AccordionItemTitle,
  AccordionItemContent,
} from 'rzp/ui/Accordion';

import { Modal, ModalContent } from 'component/Modal';
import Button, { AsyncBtn } from 'component/Button';
import DocsLink from 'merchant/components/DocsLink';
import FileUpload from 'merchant/components/File/Upload';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

const MandateFields = ['amount', 'frequency', 'debit_type'];

const PersonalDetailsFields = [
  'customer.name',
  'customer.contact',
  'customer.email',
];

const BankAccountFields = [
  'bank_account.account_number',
  'bank_account.ifsc_code',
  'bank_account.account_type',
];

const initState = {
  uploading: false,
  extractedData: {
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
    const isError = MandateFields.some(field =>
      this.errorsList.includes(field)
    );

    if (isError) {
      return 'danger';
    }

    return 'primary';
  }

  get personalDetailsStatus() {
    const isError = PersonalDetailsFields.some(field =>
      this.errorsList.includes(field)
    );

    if (isError) {
      return 'danger';
    }

    return 'primary';
  }

  get bankAccountStatus() {
    const isError = BankAccountFields.some(field =>
      this.errorsList.includes(field)
    );

    if (isError) {
      return 'danger';
    }

    return 'primary';
  }

  getDataFromExtractedData = key => {
    const data = findBy(this.state.extractedData.extracted_data, 'key', key);

    return {
      isError: this.errorsList.includes(key),
      value: data.extracted_value,
    };
  };

  onCloseClick = () => {
    this.context.confirm({
      header: 'Remove Nach Form',
      message:
        'The attached NACH form will be discarded and you will need to re-upload a new image.',
      affirmativeLabel: 'Yes, Remove',
      abort: () => {},
      action: () => {
        this.setState(initState);
      },
    });
  };

  handleChange = file => {
    this.setState({
      uploading: true,
      file,
    });

    return validateNachFile(file, this.props.id)
      .then(resp => {
        this.setState({
          extractedData: resp.data,
          uploading: false,
        });
      })
      .catch(error => {
        this.setState({
          uploading: false,
          errors: getErrorMessage(error.errors),
        });
      });
  };

  handleSubmit = () => {
    return authenticateNACHFile(this.state.file, this.props.id)
      .then(resp => {
        this.setState({
          extractedData: resp.data,
        });

        this.props.showNotification({
          type: 'success',
          message: 'NACH form uploaded successfully',
        });

        if (this.props.onClose) {
          this.props.onClose();
        } else {
          const redirectUrl = '/registration_links/' + this.props.id;

          this.props.history.push(redirectUrl);
        }
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  renderDesc = () => {
    const { uploading, errors } = this.state;

    if (uploading) {
      return (
        <React.Fragment>
          <i class="i i-info-circle" /> Please weight while we upload NACH form.
        </React.Fragment>
      );
    }

    if (this.errorsList.length) {
      return (
        <React.Fragment>
          <h5 class="text-danger">
            <i class="i i-info-circle" /> Details do not match
          </h5>
          <p>
            The highlighted details on the uploaded NACH form do not match the
            entered details. Please ensure you are uploading the correct NACH
            form.
          </p>
        </React.Fragment>
      );
    }

    if (errors.heading) {
      return (
        <React.Fragment>
          <h5 class={`text-danger`}>
            <i class="i i-info-circle" /> {errors.heading}
          </h5>

          {errors.description && (
            <p class="description">{errors.description}</p>
          )}
        </React.Fragment>
      );
    }
  };

  renderNachFieldData = key => {
    const { isError, value } = this.getDataFromExtractedData(key);

    return <div class={classList(isError && 'text-danger')}>{value}</div>;
  };

  renderNachDetails = () => {
    if (
      !this.state.extractedData.extracted_data.length ||
      this.state.uploading
    ) {
      return;
    }

    let endAt = this.renderNachFieldData('end_at'),
      startAt = this.renderNachFieldData('start_at');

    endAt = endAt && normalizeDate(endAt);

    return (
      <Accordion>
        <AccordionItem status={this.mandateStatus}>
          <AccordionItemTitle>Mandate Details</AccordionItemTitle>
          <AccordionItemContent>
            <EntityDetailRow label="Amount">
              {this.renderNachFieldData('amount')}
            </EntityDetailRow>
            <EntityDetailRow label="Frequency">
              {this.renderNachFieldData('frequency')}
            </EntityDetailRow>
            <EntityDetailRow label="Debit Type">
              {this.renderNachFieldData('debit_type')}
            </EntityDetailRow>
            <EntityDetailRow label="Debit from">{startAt}</EntityDetailRow>
            <EntityDetailRow label="Debit To">
              {endAt && 'Until cancelled'}
            </EntityDetailRow>
          </AccordionItemContent>
        </AccordionItem>
        <AccordionItem status={this.personalDetailsStatus}>
          <AccordionItemTitle>Customer's Personal Details</AccordionItemTitle>
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
          <AccordionItemTitle>Customer's Bank Details</AccordionItemTitle>
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

  render() {
    const { uploading, file } = this.state,
      isDataAval =
        this.state.extractedData.extracted_data.length ||
        this.state.errors.heading,
      disabled =
        uploading ||
        !!this.errorsList.length ||
        !this.state.extractedData.extracted_data.length;

    const isModalView = this.props.onClose;

    const contentView = (
      <div
        class={classList(
          'ModalForm',
          'Wizard',
          'UploadNACH',
          !isDataAval && 'UploadNACH--Form'
        )}
      >
        <main>
          <main-title class="main-title">Upload NACH Form</main-title>

          <p class="file-desc">
            If you received the customer's signed NACH form, you can upload it
            here, for details steps and help, please read our{' '}
            <DocsLink url="https://razorpay.com/docs/subscriptions/" />
          </p>

          <FileUpload
            showCloseBtn
            showFileSize
            showStagedFileStatus
            stagedFileStatus="error"
            maxSize="5000000"
            accept={['image/jpeg', 'image/png']}
            size="large"
            files={file ? [file] : []}
            uploadedFileName="Upload File here"
            onFileChange={this.handleChange}
            onCloseClick={this.onCloseClick}
          />

          <div class="Desc">{this.renderDesc()}</div>

          <div class="Details">{this.renderNachDetails()}</div>
        </main>

        <footer>
          {isModalView && <Button onClick={this.props.onClose}>Cancel</Button>}

          <AsyncBtn.Primary
            pendingState="Creating..."
            onClick={this.handleSubmit}
            disabled={disabled}
          >
            Create Registration Link
          </AsyncBtn.Primary>
        </footer>
      </div>
    );

    if (!isModalView) {
      return <div class="StandAloneContainer">{contentView}</div>;
    }

    return (
      <Modal
        class={classList('UploadNACHForm animate-down')}
        onClose={this.props.onClose}
      >
        <ModalContent>{contentView}</ModalContent>
      </Modal>
    );
  }
}

const getErrorMessage = ([error, status]) => {
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
};
