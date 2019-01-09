import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, DateField, SearchableSelectField } from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import Collection from 'model/collection';
import { adminFetch } from 'common/fetch';
import { ModalContent } from 'component/Modal';

import { adminPost } from 'common/fetch';

import { notifySuccess, closeModal } from 'common/modal';

const options = {
  period: ['', 'Hourly', 'Daily', 'Weekly', 'Monthly'],
};

export default class CreatePromotion extends Component {
  static title = 'Promotion/Coupon';
  static permission = 'create_promotion_coupon';

  state = { pricingPlans: {}, pending: true };

  componentWillMount() {
    adminFetch('live/pricing/merchants').then(data => {
      const pricingPlans = {};

      Object.keys(data).forEach(key => {
        pricingPlans[data[key].plan_id] = data[key].plan_name;
      });

      this.setState({ pricingPlans, pending: false });
    });
  }

  onSubmit = body => {
    let payload = {
      name: body.name,
      credit_amount: body.credit_amount,
      credit_type: 'amount',
      credits_expire: 1,
      credits_expiry_interval: body.credits_expiry_interval,
      credits_expiry_period: body.credits_expiry_period,
      purpose: body.purpose,
      pricing_plan_id: body.pricing_plan_id,
    };

    return adminPost({
      url: 'live/promotions',
      data: payload,
    }).then(response => {
      if (response) {
        let startDate = moment().unix();
        let endDate = moment().unix();

        if (body.start_at_date && body.end_at_date) {
          startDate = moment(
            `${body.start_at_date} ${body.start_at_time}`,
            'DD/MM/YYYY HH:mm'
          ).unix();
          endDate = moment(
            `${body.end_at_date} ${body.end_at_time}`,
            'DD/MM/YYYY HH:mm'
          ).unix();
        }

        let payloadCoupon = {
          entity_id: response.id,
          entity_type: 'promotion',
          code: body.coupon_code,
          max_count: body.max_count,
          start_at: startDate,
          end_at: endDate,
        };
        return adminPost({
          url: 'live/coupons',
          data: payloadCoupon,
        }).then(response => {
          if (response) {
            notifySuccess('Promotion and Coupons created successfully.');
            closeModal();
          }
        });
      }
    });
  };

  render() {
    return (
      <Form class="full-span sql-report-generator-form">
        <Field label="Name" name="name" required />
        <Field label="Purpose" name="purpose" required />
        <Field
          label="Credit Amount"
          name="credit_amount"
          placeholder="In Paise"
          required
        />

        <SelectField
          label="Credits Expiry Period"
          name="credits_expiry_period"
          required
        >
          {options.period.map((opt, idx) => (
            <option value={opt.length ? opt.toLowerCase() : ''} key={idx}>
              {opt}
            </option>
          ))}
        </SelectField>

        <Field
          label="Credits Expiry Interval"
          name="credits_expiry_interval"
          type="number"
          min="0"
          step="1"
          required
        />

        <SearchableSelectField
          trackBy="value"
          name="pricing_plan_id"
          label="Pricing Plan Id"
          options={Object.keys(this.state.pricingPlans).map(key => ({
            name: this.state.pricingPlans[key] + '(' + key + ')',
            value: key,
          }))}
        />

        <Field label="Max Count" name="max_count" />
        <Field label="Coupon Code" name="coupon_code" maxLength="10" required />

        <DateField
          required
          name="start_at_date"
          label="Starts at"
          component={<input type="time" name="start_at_time" />}
          allowToday={false}
          disablePastDates={true}
          defaultValue={moment().add(1, 'days')}
        />

        <DateField
          required
          name="end_at_date"
          label="Ends at"
          component={<input type="time" name="end_at_time" />}
          disablePastDates={true}
          allowToday={false}
          defaultValue={moment().add(3, 'days')}
        />

        <AsyncButton
          text="OK"
          class="btn"
          pendingClass="small spinner"
          type="submit"
          disabled={this.state.pending}
          onSubmit={this.onSubmit}
        />
      </Form>
    );
  }
}
