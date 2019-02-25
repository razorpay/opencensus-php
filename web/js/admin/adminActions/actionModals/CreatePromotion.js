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

import { notifySuccess, notifyError, closeModal } from 'common/modal';

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
    shouldCouponExpire: false,
  };

  componentWillMount() {
    adminFetch({
      url: 'live/pricing/merchants',
      headers: {
        'x-cross-org-id': 'org_100000razorpay',
      },
    }).then(data => {
      const pricingPlans = {};

      Object.keys(data).forEach(key => {
        pricingPlans[data[key].plan_id] = data[key].plan_name;
      });

      this.setState({ pricingPlans, pending: false });
    });
  }

  onSubmit = body => {
    if (!body.credit_amount && !body.pricing_plan_id) {
      return notifyError(
        'Please select a pricing plan or enter amount credits or do both.'
      );
    }

    body.credits_expire = parseInt(body.credits_expire);

    body.coupon_expire = parseInt(body.coupon_expire);

    let payload = {
      name: body.name,
      credit_type: 'amount',
      credits_expire: body.credits_expire,
      purpose: body.purpose,
      ...(body.partner_id && { partner_id: body.partner_id }),
      ...(body.pricing_plan_id && { pricing_plan_id: body.pricing_plan_id }),
      ...(body.credit_amount && { credit_amount: body.credit_amount * 100 }),
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
        let endDate = moment().unix();

        let payloadCoupon = {
          entity_id: response.id,
          entity_type: 'promotion',
          code: body.coupon_code,
          max_count: body.max_count,
        };

        if (body.end_at_date && body.coupon_expire === 1) {
          endDate = moment(
            `${body.end_at_date} ${body.end_at_time}`,
            'DD/MM/YYYY HH:mm'
          ).format('X');

          payloadCoupon = {
            ...payloadCoupon,
            end_at: endDate,
          };
        }

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

  handleShouldCouponExpire = e => {
    this.setState({
      shouldCouponExpire: parseInt(e.target.value),
    });
  };

  render() {
    const {
      couponCode,
      pricingPlans,
      shouldExpire,
      shouldCouponExpire,
    } = this.state;

    const { protocol, hostname } = window.location;

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
          label="Credit Amount (in Rupees)"
          type="number"
          name="credit_amount"
        />

        <SwitchField
          label="Should Credits Expire ?"
          name="credits_expire"
          onChange={this.handleShouldExpire}
          enabledLabel="Yes"
          disabledLabel="No"
          defaultValue="No"
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
          options={pricingKeys.map(key => ({
            name: pricingPlans[key] + ' (' + key + ')',
            value: key,
          }))}
        />

        <Field
            label="Partner Id"
            name="partner_id"
            maxLength="14"
        />

        <Field label="Maximum Redemptions" name="max_count" type="number" />

        <SwitchField
          label="Should Coupon Code expire ?"
          name="coupon_expire"
          onChange={this.handleShouldCouponExpire}
          enabledLabel="Yes"
          disabledLabel="No"
          defaultValue="No"
          required
        />
        {shouldCouponExpire === 1 && (
          <DateField
            required
            name="end_at_date"
            label="Ends at"
            component={
              <input type="time" name="end_at_time" defaultValue="23:59" />
            }
            disablePastDates
            allowToday={false}
            defaultValue={moment().add(3, 'days')}
          />
        )}
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
              {`${protocol}//${hostname}/#/access/signup?coupon_code=${couponCode}`}
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
