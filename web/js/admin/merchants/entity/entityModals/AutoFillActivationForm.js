import React, { Component } from 'react';
import BaseModal from 'ui/BaseModal';

import fetch from 'util/fetch';
import { notifyError } from 'common/modal';
import { removeLineBreaks } from 'util/index';

import Form from 'ui/Form';
import { SelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';

let html = '';
let bankDocument = '';

export default class AutoFillActivationForm extends Component {
  state = { scriptLoaded: false };

  componentWillMount() {
    if (!window.doT) {
      const script = document.createElement('script');

      script.onload = () => {
        this.setState({ scriptLoaded: true });
      };
      script.src =
        'https://cdnjs.cloudflare.com/ajax/libs/dot/1.1.2/doT.min.js';

      document.head.appendChild(script);
    } else {
      this.setState({ scriptLoaded: true });
    }
  }

  handleChange = e => {
    const doc = e.target.value;

    bankDocument = doc;
    if (bankDocument === 'hdfc-excel') {
      this.setState({ isTemplateLoading: false });
      return;
    }

    this.setState({ isTemplateLoading: true });
    fetch({
      url: '/admin-forms/' + bankDocument + '.html',
    })
      .then(resp => {
        const { details } = this.props.props.merchant;

        doT.templateSettings.strip = false;
        const template = doT.template(resp);

        html = template(getTemplateOptions(details));

        this.setState({ isTemplateLoading: false });
      })
      .catch(err => {
        notifyError(err);
      });
  };

  handleSubmit = body => {
    if (bankDocument === 'hdfc-excel') {
      const id = this.props.props.merchant.details.id;
      window.location = '/admin/merchant/' + id + '/hdfc_excel';
    }
    if (html) {
      const w = window.open();
      w.document.body.innerHTML = html;
    }

    return;
  };

  render() {
    return (
      <BaseModal header="Autofill Bank Activation Forms">
        {!this.state.scriptLoaded ? (
          <div class="spinner" />
        ) : (
          <Form class="full-span full-elements" style={{ width: '350px' }}>
            <SelectField
              label="Template"
              name="selectbank"
              onChange={this.handleChange}
              infoMsg={
                this.state.isTemplateLoading ? (
                  'Downloading Template..'
                ) : (
                  <span />
                )
              }
            >
              <option value="" disabled>
                --Select a template--
              </option>
              {Object.keys(templateTypes).map(option => (
                <option key={option} value={option}>
                  {templateTypes[option]}
                </option>
              ))}
            </SelectField>
            <AsyncButton
              text="Ok"
              class="btn"
              pendingClass="small spinner"
              onSubmit={this.handleSubmit}
              disabled={
                this.state.isTemplateLoading ||
                typeof this.state.isTemplateLoading === 'undefined'
              }
            />
          </Form>
        )}
      </BaseModal>
    );
  }
}

/* Resources */

const templateTypes = {
  'axis-sma': 'Axis SMA',
  'axis-ipg': 'Axis IPG',
  'hdfc-tid': 'HDFC TID Request',
  'hdfc-excel': 'HDFC Excel',
};

const getTemplateOptions = details => {
  const { merchant_details } = details;
  const now = new Date(),
    nowdate = ('0' + now.getDate()).slice(-2),
    nowmonth = ('0' + (1 + now.getMonth())).slice(-2),
    nowyear = now.getYear() + 1900;

  let reg_addr = merchant_details.business_registered_address;
  if (reg_addr) reg_addr += ', ';
  if (merchant_details.business_registered_city) {
    reg_addr += merchant_details.business_registered_city;
    if (merchant_details.business_registered_pin)
      reg_addr += '-' + merchant_details.business_registered_pin;
    reg_addr += ', ';
  }
  reg_addr += merchant_details.business_registered_state;

  let ops_addr = merchant_details.business_operation_address;
  if (ops_addr) ops_addr += ', ';
  if (merchant_details.business_operation_city) {
    ops_addr += merchant_details.business_operation_city;
    if (merchant_details.business_operation_pin)
      ops_addr += '-' + merchant_details.business_operation_pin;
    ops_addr += ', ';
  }
  ops_addr += merchant_details.business_operation_state;

  return {
    billing_label: details.billing_label || '',
    date: nowdate + '/' + nowmonth + '/' + nowyear,
    reqdate: nowdate + nowmonth + nowyear,
    reqby: 'Harshil Mathur',
    reqsign: '',
    contract_merchant: '',
    contract_corporate: '',
    contract_business: '',
    contract_software: '',
    contract_govt: '',
    contract_other: 'Y',
    contract_specify: merchant_details.business_model || '',
    mercreg_company: merchant_details.business_name || '',
    mercreg_contact: merchant_details.contact_name || '',
    mercreg_tel_business: merchant_details.contact_mobile || '',
    mercreg_tel_after: merchant_details.contact_mobile || '',
    mercreg_fax: '',
    mercreg_email: merchant_details.contact_email || '',
    mercreg_addr: removeLineBreaks(reg_addr || ''),
    mercreg_country: 'India',
    mercreg_tz: 'GMT + 5:30 (IST)',
    mercop_company: merchant_details.business_name || '',
    mercop_contact: merchant_details.contact_name || '',
    mercop_tel_business: merchant_details.contact_mobile || '',
    mercop_tel_after: merchant_details.contact_mobile || '',
    mercop_fax: '',
    mercop_email: merchant_details.contact_email || '',
    mercop_addr: removeLineBreaks(ops_addr || ''),
    cpv_head: '',
    cpv_op: '',
    merctech_contact: 'Razorpay Software Private Limited',
    merctech_pos: 'Director',
    merctech_tel_business: '+91-8003393912',
    merctech_tel_after: '+91-8003393912',
    merctech_fax: '',
    merctech_email: 'harshil@razorpay.com',
    merctech_addr:
      '35, Vishnupuri, Opp. Malviya Nagar P.O., Jagatpura Road, Jaipur - 302017, Rajasthan',
    merctech_web_addr: details.website || '',
    merctech_return_url: 'https://api.razorpay.com',
    mercsetup_auth: 'Y',
    mercsetup_purc: '',
    mercsetup_catcode: details.category || '',
    mercsetup_3: '',
    mercsetup_6: '',
    mercsetup_9: '',
    mercsetup_12: '',
    mercsetup_master: 'Y',
    mercsetup_visa: 'Y',
    mercsetup_maestro: 'Y',
    mercsetup_dmid: (details.international && 'Y') || '',
    mercsetup_smid: (!details.international && 'Y') || '',
    techpro_company: '',
    techpro_contact: '',
    techpro_pos: '',
    techpro_tel_business: '',
    techpro_tel_after: '',
    techpro_fax: '',
    techpro_email: '',
    techpro_addr: '',
    paycli_merc: '',
    paycli_third: 'Y',
    paycli_hosting: 'Amazon Web Services',
    paycli_tel: '',
    paycli_win: '',
    paycli_winver: '',
    paycli_unix: '',
    paycli_unixver: '',
    paycli_linux: 'Y',
    paycli_linuxver: '14.04',
    paycli_other: '',
    paycli_specify: '',
    payapp_custbool: '',
    payapp_cust: '',
    payapp_thirdbool: '',
    payapp_third: '',
    payapp_otherbool: 'Y',
    payapp_specify: 'Self developed by Razorpay',
    payapp_langasp: '',
    payapp_langaspx: '',
    payapp_langjsock: '',
    payapp_langjava: '',
    payapp_langperl: '',
    payapp_langoth: '',
    payapp_langspecify: '',
    payapp_sslbool: 'Y',
    payapp_4card: '',
    payapp_6card: '',
    payapp_dndcard: '',
    payapp_secyes: 'Y',
    payapp_secno: '',
    payapp_uid: 'Razorpay',
    payapp_vbvyes: 'Y',
    payapp_vbvno: '',
    payapp_mscyes: 'Y',
    payapp_mscno: '',
  };
};
