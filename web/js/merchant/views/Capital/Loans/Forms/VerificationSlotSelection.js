import React, { Component } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';

import Button, { AsyncBtn } from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { states } from 'merchant/helpers/data';
import { fetchLoanApplicationMeta, scheduleVerification } from 'merchant/reducers/capital';
import { showNotification } from 'merchant_common/reducers/notifications';

import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import ToggleWithDescription from '../../components/ToggleWithDescription';
import { isPreceedingState } from '../../utils';
import { APPLICATION_STATES, HOTJAR_TRIGGERS, VERIFICATION_TIME_SLOTS } from '../constants';

class VerificationSlotSelection extends Component {
  constructor(props) {
    super(props);
    this.state = {
      selected_time_slot: null,
      selected_date_slot: null,
      date_slot_error: null,
      selected_address: 'business',
    };
    this.timeSlots = VERIFICATION_TIME_SLOTS;
  }

  componentDidMount() {
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_APPOINTMENT_SCHEDULE);
  }

  isDateValid = (day) => {
    const begin = moment().endOf('day').add(1, 'days');
    return !moment(day).isBefore(moment()) && moment(day).isAfter(begin);
  };

  handleSelectSlot = (selected_time_slot) => {
    this.setState({
      selected_time_slot,
    });
  };

  validateDateSlot = () => {
    this.setState({
      ...(this.isDateValid(this.state.selected_date_slot)
        ? {
            date_slot_error: null,
          }
        : {
            date_slot_error: 'Please select a date atleast after 24 hours',
          }),
    });
  };

  handleDateSlotSelection = (selected_date) => {
    this.setState(
      {
        selected_date_slot: moment(selected_date).format('YYYY-MM-DD'),
      },
      this.validateDateSlot,
    );
  };

  isFormInValid = () => {
    return (
      this.state.date_slot_error || !this.state.selected_date_slot || !this.state.selected_time_slot
    );
  };

  canModify = () => {
    const { meta } = this.props.loanApplicationDetails;

    return isPreceedingState(
      meta.data.application.status,
      APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED,
    );
  };

  scheduleVerification = () => {
    if (this.isFormInValid()) return;

    const { meta, business_details, promoter_details } = this.props.loanApplicationDetails;

    if (!this.canModify()) {
      this.props._trackNavigationActions('NEXT', APPLICATION_STATES.DOCUMENT_COLLECTION_INITIATED);
      this.props.navigation.next();
      return;
    }

    const { applicant } = promoter_details.data;
    const { business } = business_details.data;

    const addressId =
      this.state.selected_address === 'business'
        ? business.addresses[0].id
        : applicant.addresses[0].id;

    const payload = {
      application_id: meta.data.application.id,
      applicant_id: applicant.id,
      applicant_address_id: addressId,
      business_address_id: addressId,
      phone_book_id: applicant.phones[0].id,
      kyc_id: applicant.kyc.kyc_id,
      slot_date: moment(this.state.selected_date_slot).format(),
      slot_timing: this.state.selected_time_slot,
    };

    return scheduleVerification(payload)
      .then((response) => {
        if (response && !response.errors) {
          return this.props.fetchLoanApplicationMeta(meta.data.application.id);
        }
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: 'Unable to schedule the document collection',
        });
      });
  };

  handleAddressChange = (address_type) => {
    this.setState({
      selected_address: address_type,
    });
  };

  getAddress = (addressType) => {
    const { business_details, promoter_details } = this.props.loanApplicationDetails;

    switch (addressType) {
      //TODO: destructure the below code
      case 'business':
        return {
          address_line1: business_details.data.business.addresses[0].address_line1,
          address_line2: business_details.data.business.addresses[0].address_line2,
          city: business_details.data.business.addresses[0].city,
          state: states[business_details.data.business.addresses[0].state] || null,
          pincode: business_details.data.business.addresses[0].pincode,
        };
      case 'residential':
        return {
          address_line1: promoter_details.data.applicant.addresses[0].address_line1,
          address_line2: promoter_details.data.applicant.addresses[0].address_line2,
          city: promoter_details.data.applicant.addresses[0].city,
          state: states[promoter_details.data.applicant.addresses[0].state] || null,
          pincode: promoter_details.data.applicant.addresses[0].pincode,
        };
    }
  };

  formatAddress = (address) => {
    return Object.values(address)
      .filter((value) => !!value)
      .join(', ');
  };

  componentDidMount() {
    const { schedule_details } = this.props.loanApplicationDetails;
    if (schedule_details.data && schedule_details.data.slot_timing) {
      const scheduleDetails = schedule_details.data;

      this.setState({
        selected_time_slot: scheduleDetails.slot_timing,
        selected_date_slot: moment(scheduleDetails.slot_date).format('YYYY-MM-DD'),
        date_slot_error: null,
        selected_address: scheduleDetails.addresses[0].address.address_type,
      });
    }
  }

  render() {
    const { business_details, promoter_details, schedule_details } =
      this.props.loanApplicationDetails;

    if (business_details.loading || promoter_details.loading || schedule_details.loading)
      return <FormLoader />;

    const canModify = this.canModify();

    return (
      <div>
        <Form layout="tabular" style={{ width: 524 }}>
          <Input.Group label="Address Type" className="InputGroup--inline" required>
            <div className="Input-content">
              <ToggleWithDescription
                name="address_type"
                title="Business Address"
                description={this.formatAddress(this.getAddress('business'))}
                selected={this.state.selected_address === 'business'}
                onClick={() => this.handleAddressChange('business')}
                disabled={!canModify}
                style={{ marginBottom: 12 }}
              />
              <ToggleWithDescription
                name="address_type"
                title="Residential Address"
                description={this.formatAddress(this.getAddress('residential'))}
                selected={this.state.selected_address === 'residential'}
                onClick={() => this.handleAddressChange('residential')}
                disabled={!canModify}
              />
            </div>
          </Input.Group>
          <Input.ToCalendar
            required
            data-name="date_slot"
            label="Date Selection"
            propagatedError={this.state.date_slot_error}
            defaultValue={moment(
              this.state.selected_date_slot ? this.state.selected_date_slot : new Date(),
              'X',
            )}
            value={this.state.selected_date_slot}
            onChange={this.handleDateSlotSelection}
            size="half_small"
            addonAfter={<i className="i i-date-range" />}
            placement="topLeft"
            allowToday={false}
            mature
            disablePastDates={true}
            disabled={!canModify}
          />
          <Input.Group label="Time Slot Selection" className="InputGroup--inline" required>
            <div className="Input-content">
              <div style={{ display: 'flex', flexWrap: 'wrap' }}>
                {this.timeSlots.map((slot) => (
                  <ToggleWithDescription
                    name="time_slot"
                    size="small"
                    title={slot.text}
                    selected={this.state.selected_time_slot === slot.value}
                    onClick={() => this.handleSelectSlot(slot.value)}
                    style={{ marginRight: 12, marginBottom: 12 }}
                    disabled={!canModify}
                  />
                ))}
              </div>
            </div>
          </Input.Group>
          <div className="actions pull-right">
            <Button.Transparent
              onClick={() => {
                this.props._trackNavigationActions(
                  'BACK',
                  APPLICATION_STATES.NACH_CREATION_PENDING,
                );
                this.props.navigation.back();
              }}
            >
              <i className="i i-chevron-left" />
              Back
            </Button.Transparent>
            <AsyncBtn.Primary
              disabled={this.isFormInValid()}
              type="submit"
              className="btn btn-primary m-l"
              onClick={this.scheduleVerification}
            >
              Next
              <i className="i i-chevron-right" />
            </AsyncBtn.Primary>
          </div>
        </Form>
      </div>
    );
  }
}

export default connect(
  (state) => ({
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    fetchLoanApplicationMeta,
    scheduleVerification,
    showNotification,
  },
)(VerificationSlotSelection);
