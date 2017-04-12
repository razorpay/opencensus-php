import { Component } from 'react'
import { connect } from 'react-redux'
import { Field, reduxForm } from 'redux-form'
import AsyncButton from 'react-async-button'
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton'
import InputField from 'rzp/ui/Forms/InputField'
import Alert from 'rzp/ui/Forms/Alert'
import { required, email, phone } from 'rzp/utils/validators'
import { saveFile } from 'merchant/modules/activation'
import { showNotification } from 'merchant/modules/notifications'

@connect(
  (state) => state.activation,
  { saveFile, showNotification }
)
@reduxForm({
  form: 'activationDocumentsUpload',
  destroyOnUnmount: false,
})
export default class DocumentsUploadForm extends Component {
  constructor() {
    super(...arguments)
    this.state = {
      errors: null
    }
  }

  componentWillMount() {
    if (!this.props.initialized) {
      this.props.initialize(this.props.data)
    }
  }

  saveFile = (event, fieldName) => {
    let files = event.target.files

    return this.props.saveFile(files[0], fieldName).then((response) => {
      this.props.showNotification({
        type: 'success',
        message: 'File uploaded successfully'
      })
    }).catch(({ errors }) => {
      this.props.showNotification({
        type: 'error',
        message: errors
      })
    })
  }

  render() {
    let { handleSubmit } = this.props

    return (
      <div class='panel'>
        <div class='panel-body'>
          <div class='row'>
            <div class='col-md-offset-2 col-md-10'>
              <h4 class='wizard-header'>Documents Upload</h4>
            </div>
          </div>

          <div class='row'>
            <div class='col-lg-8 col-md-10 col-sm-12'>
              <Alert type='error' message={this.state.errors} />

              <form class='form-horizontal'>
                <fieldset>
                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Business Registration Proof</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Upload scan of following:
                        <ul>
                          <li>Sales Tax/Service Tax or Shop Act Registration (Mandatory, if Partnership/Proprietership firm)</li>
                          <li>Certificate of Incorporation (Mandatory if private limited)</li>
                          <li>Trust/Society/NGO etc. registration proof</li>
                        </ul>
                      </span>
                      <FileUploadInputButton
                        accept='image/jpeg,image/png,application/pdf,application/x-pdf'
                        onChange={(event) => {
                          return this.saveFile(event, 'business_proof')
                        }}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Business PAN</label>
                    <div class='col-md-9'>
                      <span class='help-block'>Company/Partnership/LLP PAN Card (Sole Proprietor can use personal PAN)</span>
                      <FileUploadInputButton
                        accept='image/jpeg,image/png,application/pdf,application/x-pdf'
                        onChange={(event) => {
                          return this.saveFile(event, 'business_pan_proof')
                        }}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Company's Bank Account Statement with Address</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Upload following:
                        <ul>
                          <li>Bank Account Statement (of last three months or since opening of account, whichever is earlier)</li>
                        </ul>
                      </span>

                      <FileUploadInputButton
                        accept='image/jpeg,image/png,application/pdf,application/x-pdf'
                        onChange={(event) => {
                          return this.saveFile(event, 'address_proof')
                        }}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <label class='col-md-3 control-label label-required'>Authorised Signatory Address Proof</label>
                    <div class='col-md-9'>
                      <span class='help-block'>
                        Upload address proof (preferably scanned copy of passport) of at least one authorised signatory.
                        In case of sole proprietership, upload your personal address proof.
                      </span>
                      <FileUploadInputButton
                        accept='image/jpeg,image/png,application/pdf,application/x-pdf'
                        onChange={(event) => {
                          return this.saveFile(event, 'promoter_address_proof')
                        }}
                      />
                    </div>
                  </div>

                  <div class='form-group'>
                    <div class='col-md-offset-3 col-md-9'>
                      <div class='btn-toolbar'>
                        <AsyncButton
                          class='btn btn-default pull-left'
                          text='Back'
                        />

                        <AsyncButton
                          class='btn btn-default pull-right'
                          text='Next'
                        />
                      </div>
                    </div>
                  </div>
                </fieldset>
              </form>
            </div>
          </div>
        </div>
      </div>
    )
  }
}
