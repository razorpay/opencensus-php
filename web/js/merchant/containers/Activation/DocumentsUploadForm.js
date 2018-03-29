import { Component } from 'react';
import { Field } from 'redux-form';
import AsyncButton from 'react-async-button';
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';
import Fieldset from 'rzp/ui/Forms/Fieldset';

export default class DocumentsUploadForm extends Component {
  render() {
    let { saveFile, goBack, goNext, accountId } = this.props;
    let files = this.props.uploadedFiles;
    let { business_type } = this.props.data;

    return (
      <form class="form-horizontal">
        <Fieldset disabled={this.props.data.locked}>
          {accountId ? null : (
            <div>
              <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  Business Registration Proof
                </label>
                <div class="col-md-9">
                  <span class="help-block">
                    Upload scan of the following:
                    <ul>
                      <li>
                        Sales Tax/Service Tax or Shop Act Registration or GST
                        Certificate (mandatory, if Proprietorship firm)
                      </li>
                      <li>Partnership Deed (mandatory, if Partnership firm)</li>
                      <li>
                        Certificate of Incorporation (mandatory, if Private
                        Limited or LLP)
                      </li>
                      <li>
                        Registration Proof or Certificate (Trust/Society/NGO
                        etc.)
                      </li>
                    </ul>
                  </span>
                  <FileUploadInputButton
                    accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                    uploadedFileName={files.business_proof}
                    maxSize="8000000"
                    onChange={event => {
                      return saveFile(event, 'business_proof');
                    }}
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-3 control-label label-required">
                  Business PAN
                </label>
                <div class="col-md-9">
                  <span class="help-block">
                    Company/Partnership/LLP PAN Card (Sole Proprietor can use
                    their personal PAN)
                  </span>
                  <FileUploadInputButton
                    accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                    uploadedFileName={files.business_pan_proof}
                    maxSize="8000000"
                    onChange={event => {
                      return saveFile(event, 'business_pan_proof');
                    }}
                  />
                </div>
              </div>
            </div>
          )}

          <div class="form-group">
            <label class="col-md-3 control-label label-required">
              Company's Bank Account Statement with Address
            </label>
            <div class="col-md-9">
              <span class="help-block">
                Upload following:
                <ul>
                  <li>
                    Bank Account Statement (last three months or since opening
                    of account) OR cancelled cheque issued in the name of the
                    registered business
                  </li>
                </ul>
              </span>

              <FileUploadInputButton
                accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                uploadedFileName={files.address_proof}
                maxSize="8000000"
                onChange={event => {
                  return saveFile(event, 'address_proof');
                }}
              />
            </div>
          </div>

          {accountId ? (
            <div class="form-group">
              <label class="col-md-3 control-label label-required">
                PAN Card
              </label>
              <div class="col-md-9">
                <span class="help-block">Promoter/Individual PAN Card.</span>
                <FileUploadInputButton
                  accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                  uploadedFileName={files.promoter_pan_proof}
                  maxSize="8000000"
                  onChange={event => {
                    return saveFile(event, 'promoter_pan_proof');
                  }}
                />
              </div>
            </div>
          ) : (
            <div class="form-group">
              <label class="col-md-3 control-label label-required">
                Authorised Signatory's Address Proof
              </label>
              <div class="col-md-9">
                <span class="help-block">
                  Upload both sides of the government issued photo ID
                  (Passport/Aadhaar/Driving License/Election Card)
                </span>
                <FileUploadInputButton
                  accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                  uploadedFileName={files.promoter_address_proof}
                  maxSize="8000000"
                  onChange={event => {
                    return saveFile(event, 'promoter_address_proof');
                  }}
                />
              </div>
            </div>
          )}

          {/* Only show it when the business type is NGO */}
          {business_type == 7 && !accountId
            ? [
                <div class="form-group" key="form_12a">
                  <label class="col-md-3 control-label label-required">
                    Form 12A Allotment Letter
                  </label>
                  <div class="col-md-9">
                    <span class="help-block">Mandatory for NGOs</span>
                    <FileUploadInputButton
                      accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                      uploadedFileName={files.ngo_12a_proof}
                      maxSize="8000000"
                      onChange={event => {
                        return saveFile(event, 'ngo_12a_proof');
                      }}
                    />
                  </div>
                </div>,
                <div class="form-group" key="form_80g">
                  <label class="col-md-3 control-label label-required">
                    Form 80G Allotment Letter
                  </label>
                  <div class="col-md-9">
                    <span class="help-block">Mandatory for NGOs</span>
                    <FileUploadInputButton
                      accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                      uploadedFileName={files.ngo_80g_proof}
                      maxSize="8000000"
                      onChange={event => {
                        return saveFile(event, 'ngo_80g_proof');
                      }}
                    />
                  </div>
                </div>,
              ]
            : null}

          <div class="form-group">
            <div class="col-md-offset-3 col-md-9">
              <div class="btn-toolbar">
                <AsyncButton
                  class="btn btn-default pull-left"
                  text="Back"
                  onClick={goBack}
                />

                <AsyncButton
                  class="btn btn-default pull-right"
                  text="Next"
                  onClick={goNext}
                />
              </div>
            </div>
          </div>
        </Fieldset>
      </form>
    );
  }
}
