import React, { Component } from 'react';

import EntityRow from 'ui/EntityRow';
import AsyncButton from 'ui/AsyncButton';
import fetch, { adminFetch } from 'common/fetch';
import { notifyError, notifySuccess } from 'common/modal';
import { titleCase } from 'common/util';

import Form from 'ui/Form';
import Field, { SelectField, TextAreaField } from 'ui/Field';
import Table from 'ui/Table';

export default class BusinessDetails extends Component {
  state = { panVerified: false, companyInfo: null };

  verifyPAN(signatories, pan_name, pan_number) {
    for (let i in signatories) {
      const person = signatories[i];
      if (
        person.PAN_DIN.toUpperCase() === pan_number.toUpperCase() &&
        person.Name.toUpperCase() === pan_name.toUpperCase()
      ) {
        this.setState({ panVerified: true });
      }
    }
  }

  getCompanyData = () => {
    const cin = this.props.merchant_details.company_cin;

    return fetch({ url: '/admin/companies/' + cin + '/info' })
      .then(data => {
        if (data) {
          this.setState({ companyInfo: data });

          this.verifyPAN(
            data.signatories,
            this.props.merchant_details.promoter_pan_name,
            this.props.merchant_details.promoter_pan
          );
        }
      })
      .catch(function() {
        notifyError('Company Info could not be fetched');
      });
  };

  render() {
    const { merchant_details: merchantDetails, title } = this.props;

    return (
      <div class="container">
        <header class="m-b">{title}</header>
        {!merchantDetails ? (
          <div class="spinner center m-t" />
        ) : (
          <Form
            class="full-span full-elements limited"
            style={{ maxWidth: '650px' }}
          >
            <SelectField
              label="Organisation Type"
              name="business_type"
              defaultValue={merchantDetails.business_type}
              disabled
            >
              <option value="1">Proprietorship</option>
              <option value="2">Individual</option>
              <option value="3">Partnership</option>
              <option value="4">Private Limited</option>
              <option value="5">Public Limited</option>
              <option value="6">LLP</option>
              <option value="7">NGO</option>
              <option value="8">Educational Institutes</option>
              <option value="9">Trust</option>
              <option value="10">Society</option>
              <option value="11">Not yet registered</option>
              <option value="12">Other</option>
            </SelectField>

            <Field
              label="Full Business Name"
              name="business_name"
              defaultValue={merchantDetails.business_name}
              disabled
            />

            <Field
              label={
                <span>
                  Doing Business As<br />(If Different from Above)
                </span>
              }
              name="business_dba"
              type="email"
              defaultValue={merchantDetails.business_dba}
              disabled
            />

            <SelectField
              label="International Payments Required?"
              name="business_international"
              defaultValue={merchantDetails.business_international ? '1' : '0'}
              disabled
            >
              <option value="0">No</option>
              <option value="1">Yes</option>
            </SelectField>

            <Field
              label="Website Address"
              name="business_website"
              defaultValue={merchantDetails.business_website}
              disabled
            />

            <Field
              label="Payments Accepted for (Also mention B2b or B2C)"
              name="business_paymentdetails"
              defaultValue={merchantDetails.business_paymentdetails}
              disabled
            />

            <TextAreaField
              label="Business Model"
              name="business_model"
              helpMsg="Please give a brief explanation of your business model and future plans (Essential for startups)"
              defaultValue={merchantDetails.business_model}
              disabled
            />

            <Field
              label="Registered Address"
              name="business_registered_address"
              defaultValue={merchantDetails.business_registered_address}
              disabled
            />

            <Field
              label="Registration Address State"
              name="business_registered_state"
              defaultValue={merchantDetails.business_registered_state}
              disabled
            />

            <Field
              label="Registered Address City"
              name="business_registered_city"
              defaultValue={merchantDetails.business_registered_city}
              disabled
            />

            <Field
              label="Registered Address Pincode"
              name="business_registered_pin"
              defaultValue={merchantDetails.business_registered_pin}
              disabled
            />

            <Field
              label="Operation Address same as Registered Address"
              name="or_same"
              defaultValue={merchantDetails.or_same}
              disabled
            />

            <Field
              label="Operation Address"
              name="business_operation_address"
              defaultValue={merchantDetails.business_operation_address}
              disabled
            />

            <Field
              label="Operation Address State"
              name="business_operation_state"
              defaultValue={merchantDetails.business_operation_state}
              disabled
            />

            <Field
              label="Operation Address City"
              name="business_operation_city"
              defaultValue={merchantDetails.business_operation_city}
              disabled
            />

            <Field
              label="Operation Address Pincode"
              name="business_operation_pin"
              defaultValue={merchantDetails.business_operation_pin}
              disabled
            />

            <Field
              label="Company CIN"
              name="company_cin"
              helpMsg={() => (
                <AsyncButton
                  onClick={this.getCompanyData}
                  class="link"
                  pendingClass="link btn-pending"
                >
                  Verify
                  <div class="dot-loader">.</div>
                </AsyncButton>
              )}
              defaultValue={merchantDetails.company_cin}
              disabled
            />

            {this.state.companyInfo && (
              <div class="field">
                {Object.keys(this.state.companyInfo.company).map(key => {
                  let className = 'pill pill-wrap';
                  if (key === 'defaulter') {
                    if (this.state.companyInfo.company[key]) {
                      className += ' label-danger';
                    } else {
                      className += ' label-success';
                    }
                  } else {
                    className += ' label-semi-muted';
                  }
                  return (
                    <EntityRow
                      class="info-block no-padding m-t m-b"
                      key={key}
                      label={`${titleCase(key)}:`}
                      value={() => (
                        <span class={className}>
                          {JSON.stringify(this.state.companyInfo.company[key])}
                        </span>
                      )}
                    />
                  );
                })}
              </div>
            )}

            {this.state.companyInfo && (
              <div class="field">
                <label>Signatories:</label>
                <Table
                  customClass="custom-table"
                  items={this.state.companyInfo.signatories}
                  fields={_getCompanyInfoFields()}
                />
              </div>
            )}

            <Field
              label="Company PAN"
              name="company_pan"
              helpMsg={() => (
                <a
                  class="link"
                  target="_blank"
                  href={`https://incometaxindiaefiling.gov.in/e-Filing/Services/KnowYourJurisdictionLink.html?panOfDeductee=${
                    merchantDetails.company_pan
                  }`}
                >
                  Verify
                </a>
              )}
              defaultValue={merchantDetails.company_pan}
              disabled
            />

            <Field
              label={
                <span>
                  Name on PAN Card <br />(as provided above)
                </span>
              }
              name="company_pan_name"
              helpMsg="Mandatory for Companies"
              defaultValue={merchantDetails.company_pan_name}
              disabled
            />

            <Field
              label={
                <span>
                  EPAN of any 1 authorised signatory/promoter/director <br />(as
                  provided above)
                </span>
              }
              name="promoter_pan"
              defaultValue={merchantDetails.promoter_pan}
              disabled
            />

            <Field
              label={
                <span>
                  Name on PAN Card <br />(as provided above)
                </span>
              }
              name="promoter_pan_name"
              defaultValue={merchantDetails.promoter_pan_name}
              disabled
            />

            <div class="field">
              <label>Signatory PAN Verified</label>
              <i
                class={`i ${
                  this.state.panVerified
                    ? 'i-yes text-success'
                    : 'i-no text-danger'
                }`}
              />
              <div class="info-block">
                <i class="i i-info-circle" />
                This only verifies if the Signatory PAN Number and Name on the
                Card provided here matches an entry in the signatory table
                above.
              </div>
            </div>
          </Form>
        )}
      </div>
    );
  }
}

/* Resource */

function _getCompanyInfoFields() {
  return [
    ['Name', item => item.Name],
    ['PAN/DIN', item => item.PAN_DIN],
    ['Start Date', item => item.StartDate],
    ['End Date', item => item.EndDate],
    [
      'Defaulter',
      item => (
        <i
          style={{ fontSize: '16px' }}
          class={`i ${
            item.Defaulter ? 'i-warning text-danger' : 'i-smile text-success'
          }`}
        />
      ),
    ],
  ];
}
