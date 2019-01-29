import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, {
  SwitchField,
  SelectField,
  DateField,
  SearchableSelectField,
} from 'ui/Field';
import AsyncButton from 'ui/AsyncButton';
import { adminFetch } from 'common/fetch';

import { adminPost } from 'common/fetch';

import { notifySuccess, closeModal } from 'common/modal';

const options = {
  period: ['', 'Hourly', 'Daily', 'Weekly', 'Monthly'],
};

export default class CreatePromotion extends Component {
  static title = 'Promotion/Coupon';
  static permission = 'create_promotion_coupon';

  state = {
    pricingPlans: {},
    couponCode: '',
    shouldExpire: false,
    pending: true,
  };

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
    body.credits_expire = parseInt(body.credits_expire);

    let payload = {
      name: body.name,
      credit_amount: body.credit_amount,
      credit_type: 'amount',
      credits_expire: body.credits_expire,
      purpose: body.purpose,
      pricing_plan_id: body.pricing_plan_id,
    };

    if (body.credits_expire) {
      payload = {
        ...payload,
        ...{
          credits_expiry_interval: body.credits_expiry_interval,
          credits_expiry_period: 'daily',
        },
      };
    }

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

  handleCouponCodeChange = e => {
    const code = e.target.value.toUpperCase();
    const re = /^[A-Z0-9]*$/;

    if (code === '' || re.test(code)) {
      this.setState({ couponCode: code });
    }
  };

  handleShouldExpire = e => {
    this.setState({
      shouldExpire: parseInt(e.target.value),
    });
  };

  render() {
    const { couponCode, pricingPlans, shouldExpire } = this.state;

    const pricingKeys = Object.keys(pricingPlans);

    return (
      <Form class="full-span sql-report-generator-form promotion-form">
        <Field
          label="Coupon Code"
          name="coupon_code"
          maxLength="10"
          value={couponCode}
          required
          onChange={this.handleCouponCodeChange}
        />
        <input type="hidden" name="name" defaultValue={couponCode} />
        <Field label="Purpose" name="purpose" required />
        <Field
          label="Credit Amount (in Paise)"
          type="number"
          name="credit_amount"
          required
        />

        <SwitchField
          label="Should Credits Expire ?"
          name="credits_expire"
          onChange={this.handleShouldExpire}
          enabledLabel="Yes"
          disabledLabel="No"
          defaultValue="No"
          required
        />

        {shouldExpire === 1 && (
          <>
            <Field
              label="No of Days before Credits expire"
              name="credits_expiry_interval"
              type="number"
              min="0"
              step="1"
              required
            />
          </>
        )}
        <SearchableSelectField
          name="pricing_plan_id"
          label="Pricing Plan Id"
          trackBy="value"
          defaultValue={pricingKeys[0]}
          options={pricingKeys.map(key => ({
            name: pricingPlans[key] + ' (' + key + ')',
            value: key,
          }))}
        />

        <Field label="Maximum Redemptions" name="max_count" type="number" />

        <DateField
          required
          name="start_at_date"
          label="Starts at"
          component={
            <input type="time" name="start_at_time" defaultValue="23:59" />
          }
          disablePastDates={true}
          defaultValue={moment()}
        />

        <DateField
          required
          name="end_at_date"
          label="Ends at"
          component={
            <input type="time" name="end_at_time" defaultValue="23:59" />
          }
          disablePastDates={true}
          allowToday={false}
          defaultValue={moment().add(3, 'days')}
        />
        {couponCode !== '' && (
          <div class="field">
            <label>Signup Link:</label>
            <span
              class="link m-l signup-promo-link"
              style={{
                width: '50%',
                border: 'none',
              }}
            >
              https://dashboard.razorpay.com/#/access/signup?coupon_code={
                couponCode
              }
            </span>
          </div>
        )}

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
