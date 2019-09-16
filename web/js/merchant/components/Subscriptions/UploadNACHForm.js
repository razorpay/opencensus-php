import { connect } from 'react-redux';
import { classList } from 'common/util';
import { findBy, normalizeDate } from 'rzp/utils/rzp-utils';

import { validateNachFile } from 'merchant/modules/registration_link';
import { showNotification } from 'rzp/modules/notifications';

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
  auth_link_id: 'inv_DHKApkctzcP6FL',
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
        'The attached NACH form will be discarded and you will need to reupload a new image.',
      affirmativeLabel: 'Yes, Remove',
      abort: () => {},
      action: () => {},
    });

    this.setState(initState);
  };

  handleChange = file => {
    this.setState({
      uploading: true,
    });

    return validateNachFile(file, this.state.auth_link_id)
      .then(resp => {
        this.setState({
          extractedData: resp.data,
          uploading: false,
        });
      })
      .catch(error => {
        this.setState({
          uploading: false,
          extractedData: dummyJSON,
        });

        this.props.showNotification({
          type: 'error',
          message: error.errors,
        });
      });
  };

  handleSubmit = () => {};

  renderDesc = () => {
    const { uploading } = this.state;

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
    const { uploading } = this.state,
      isDataAval = this.state.extractedData.extracted_data.length,
      disabled =
        uploading ||
        !!this.errorsList.length ||
        !this.state.extractedData.extracted_data.length;

    return (
      <Modal
        class={classList(
          'ModalForm UploadNACHForm animate-down',
          isDataAval && 'UploadNACHForm-fulldata'
        )}
        onClose={this.props.closeModal}
      >
        <ModalContent>
          <div class="NACH--Upload Wizard">
            <main>
              <main-title class="main-title">Upload NACH Form</main-title>

              <p class="desc">
                If you received the customer's signed NACH form, you can upload
                it here, for details steps and help, please read our{' '}
                <DocsLink url="https://razorpay.com/docs/subscriptions/" />
              </p>

              <FileUpload
                showCloseBtn
                showFileSize
                showStagedFileStatus
                stagedFileStatus="error"
                maxSize="8000000"
                accept={[
                  'image/jpeg',
                  'image/png',
                  'application/pdf',
                  'application/x-pdf',
                ]}
                size="large"
                uploadedFileName="Upload File here"
                onFileChange={this.handleChange}
                onCloseClick={this.onCloseClick}
              />

              <div class="Desc">{this.renderDesc()}</div>

              <div class="Details">{this.renderNachDetails()}</div>
            </main>

            <footer>
              <Button onClick={this.props.closeModal}>Cancel</Button>

              <AsyncBtn.Primary
                pendingState="Creating..."
                onClick={this.handleSubmit}
                disabled={disabled}
              >
                Create Registration Link
              </AsyncBtn.Primary>
            </footer>
          </div>
        </ModalContent>
      </Modal>
    );
  }
}

const dummyJSON = {
  success: false,
  errors: {
    not_matching: ['customer.name', 'customer.contact'],
  },
  enhanced_image: 'https://dummycdn.razorpay.com/logos/D8Xhiby96sNStz.jpg',
  extracted_data: [
    {
      key: 'bank_account.account_number',
      expected_value: '1111111111111',
      extracted_value: '1111111111111',
    },
    {
      key: 'bank_account.ifsc_code',
      expected_value: 'HDFC0001233',
      extracted_value: 'HDFC0001233',
    },
    {
      key: 'bank_account.account_type',
      expected_value: 'savings',
      extracted_value: 'savings',
    },
    {
      key: 'merchant.name',
      expected_value: 'TEST ACCOUNT',
      extracted_value: 'TEST ACCOUNT',
    },
    {
      key: 'customer.name',
      expected_value: 'AVINASH100000POP',
      extracted_value: 'GAURAV KUMAR',
    },
    {
      key: 'customer.email',
      expected_value: 'avinash100000pop1@a.c',
      extracted_value: 'gaurav.kumar12@example.com',
    },
    {
      key: 'customer.contact',
      expected_value: '9483159238',
      extracted_value: '9123456780',
    },
    {
      key: 'utility_code',
      expected_value: 'NACH00000000013149',
      extracted_value: 'NACH00000000013149',
    },
    {
      key: 'debit_type',
      expected_value: 'maximum_amount',
      extracted_value: 'maximum_amount',
    },
    {
      key: 'frequency',
      expected_value: 'yearly',
      extracted_value: 'yearly',
    },
    {
      key: 'type',
      expected_value: 'create',
      extracted_value: 'create',
    },
    {
      key: 'umrn',
      expected_value: null,
      extracted_value: null,
    },
    {
      key: 'amount',
      expected_value: 10000,
      extracted_value: 10000,
    },
    {
      key: 'sponsor_bank_code',
      expected_value: 'RATN0TREASU',
      extracted_value: 'RATN0TREASU',
    },
    {
      key: 'reference_1',
      expected_value: '121211212112121121',
      extracted_value: '121211212112121121',
    },
    {
      key: 'reference_2',
      expected_value: '121211212112121121',
      extracted_value: '121211212112121121',
    },
    {
      key: 'created_at',
      expected_value: '19/08/2019',
      extracted_value: '19/08/2019',
    },
    {
      key: 'start_at',
      expected_value: '07/12/2025',
      extracted_value: '07/12/2025',
    },
    {
      key: 'end_at',
      expected_value: null,
      extracted_value: null,
    },
  ],
};
