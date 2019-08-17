import Input from 'component/Input';

export default ({ handleChange, handleFileUpload }) => {
  return (
    <div class="form-body">
      <Input.Textarea
        required
        name="use_case"
        className="Input--vTop"
        label="Use Case"
        placeholder="Your use case for the product and business model"
        onChange={handleChange}
      />

      <Input.Select
        required
        label="Transferring To"
        options={OPTIONS}
        name="settling_to"
        className="Input--vTop"
        searchEnabled={false}
        onChange={handleChange}
        placeholder="Transferring Payments to?"
      />

      <Input.File
        required
        autoFocus
        autoRender
        showCloseBtn
        name="file"
        className="Input--vTop"
        label="Signed Vendor Agreement"
        _accept={['pdf', 'image']}
        uploadedFileName="signed_vendor_agreement"
        onChange={handleFileUpload}
        onCloseClick={handleFileUpload}
        _showAcceptInfo={false}
        _showStagedFileStatus={false}
        description={
          <React.Fragment>
            <i class="i i-info-outline m-r" />
            <span>
              As a sample, upload a signed agreement executed with your
              3rd-parties or vendors
            </span>
          </React.Fragment>
        }
        _when={() => {
          return false;
        }}
      />
    </div>
  );
};

const OPTIONS = [
  {
    label: '--Select--',
    name: '',
  },
  {
    label: 'Third-party businesses',
    name: 'Businesses',
  },
  {
    label: 'Own bank accounts',
    name: 'Own Accounts',
  },
  {
    label: 'Individuals',
    label: 'Individuals',
  },
];
