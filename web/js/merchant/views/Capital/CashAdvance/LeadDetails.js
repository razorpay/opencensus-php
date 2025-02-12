import React, { Component } from 'react';
import { connect } from 'react-redux';

import { AsyncBtn } from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { updateFeatures } from 'merchant/reducers/config';

import { ESTIMATED_AMOUNT_REQUIREMENTS } from './constants';

class LeadDetails extends Component {
  constructor(props) {
    super(props);
    this.avaialbleCreditOptions = ESTIMATED_AMOUNT_REQUIREMENTS;

    this.seedOptions = Object.entries(this.avaialbleCreditOptions).map(
      ([volume, volume_label]) => ({
        label: volume_label,
        name: volume,
      }),
    );

    this.state = {
      formValues: {
        name: '',
        contact: '',
        amount: this.seedOptions[0].value,
        purpose: '',
      },
    };
  }

  handleChange = ({ target }) => {
    const fieldValue = target.value;
    const fieldName = target.name;
    this.setState((prevState) => ({
      formValues: {
        ...prevState.formValues,
        [fieldName]: fieldValue,
      },
    }));
  };

  createLead = async () => {
    const { createFDTicket, onRaiseRequest } = this.props;
    const { name, contact, amount, purpose } = this.state.formValues;

    const content = `<div dir="ltr"><div>Hey, I am interested to enroll in Line Of Credit<br><br>
      Merchant ID: <strong>${this.props.user.current}</strong>.
      Name: <strong>${name}</strong>.<br>
      Contact: <strong>${contact}</strong>.<br>
      Expected Business Need: <strong>${this.avaialbleCreditOptions[amount]}</strong>.<br>
      Purpose: <strong>${purpose}</strong>.
    `;

    await this.props.updateFeatures({
      features: {
        loc_stage_2: '1',
      },
    });
    return createFDTicket(content, this.props.user).then(onRaiseRequest);
  };

  render() {
    const { name, contact, amount, purpose } = this.state.formValues;

    return (
      <Form className="Form withdrawal-lead-form" onChange={this.handleChange}>
        <div className="p-all">
          <div className="flex">
            <div className="m-r Input--vTop Input--small">
              <Input
                label="Your Name"
                key="name"
                name="name"
                value={name}
                placeholder="Name"
                autoFocus={true}
                required
              />
            </div>
            <div className="m-l Input--vTop Input--small">
              <Input
                type="number"
                addonBefore={<small>+91</small>}
                label="Contact Number"
                key="second_name"
                name="contact"
                value={contact}
                placeholder="Contact number"
                required
              />
            </div>
          </div>
          <div className="m-b Input--vTop" style={{ margin: '24px 0' }}>
            <Input.Select
              key="amount"
              value={amount}
              addonBefore={<small>₹</small>}
              name="amount"
              placeholder=""
              options={this.seedOptions}
              required
              size="medium"
              description="The maximum credit amount you will need based on business needs in one time."
              label="How much credit line will your business need?"
            />
          </div>
          <Input.Textarea
            className="Input--vTop m-b p-b"
            size="large"
            value={purpose}
            key="purpose"
            label="How are you planning to use the amount?"
            name="purpose"
            placeholder="Use this space to describe the purpose of availing cash advance."
          />
          <div className="m-t">
            <AsyncBtn.Primary type="submit" onClick={this.createLead}>
              Start your application
            </AsyncBtn.Primary>
          </div>
        </div>
      </Form>
    );
  }
}

export default connect((state) => ({ user: state.session.user }), {
  updateFeatures,
})(LeadDetails);
