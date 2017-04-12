import { Component } from 'react'
import { Field } from 'redux-form'
import AsyncButton from 'react-async-button'
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton'
import ActivationBaseHOC from './ActivationBase'

@ActivationBaseHOC
export default class DocumentsUploadForm extends Component {
  render() {
    let { saveFile, gotoTab } = this.props

    return (
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
                  return saveFile(event, 'business_proof')
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
                  return saveFile(event, 'business_pan_proof')
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
                  return saveFile(event, 'address_proof')
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
                  return saveFile(event, 'promoter_address_proof')
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
                  onClick={() => gotoTab(4)}
                />

                <AsyncButton
                  class='btn btn-default pull-right'
                  text='Next'
                  onClick={() => gotoTab(6)}
                />
              </div>
            </div>
          </div>
        </fieldset>
      </form>
    )
  }
}
