import React, { useState } from 'react';
import RTracking from 'react-tracking';
import { connect } from 'react-redux';
import { compose } from 'redux';
import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import { merchantFetch } from 'merchant/utils/ajax';
import Popover, { PopoverBody } from 'common/ui/Popover';
import ModalHeader from 'common/ui/ModalHeader';
import { isEmail } from 'common/utils/validators';
import { updateSession } from 'merchant/reducers/session';
import User from 'merchant/models/User';
import SuccessTnCGeneratedModal from './SuccessTnCGeneratedModal';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const GenerateTnCPage = ({ onCloseModal, openModal, tracking, updateSession, user, mode }) => {
  const [isGoodsType, setIsGoods] = useState(true);
  const [isDeclarationCheck, setIsDeclarationCheck] = useState(true);
  const [error, setError] = useState('');
  const initialValues = {
    shipping_period: '0-2 days',
    refund_request_period: '1-2 days',
    refund_process_period: '1-2 days',
    warranty_period: '3 months',
    support_email: user.contact_email || '',
  };
  const [formValues, setFormValues] = useState({ ...initialValues });

  const trackEvent = tracking.trackEvent;

  const FIELD_OPTIONS = {
    shipping_period: [
      { label: '0-2 days', name: '0-2 days' },
      { label: '3-5 days', name: '3-5 days' },
      { label: '6-8 days', name: '6-8 days' },
      { label: 'More than 8 days', name: '8 days' },
    ],
    refund_period: [
      { label: '1-2 days', name: '1-2 days' },
      { label: '3-5 days', name: '3-5 days' },
      { label: '6-8 days', name: '6-8 days' },
      { label: '9-15 days', name: '9-15 days' },
      { label: '16-30 days', name: '16-30 days' },
    ],
    warranty: [
      { label: 'Upto 3 months', name: '3 months' },
      { label: 'Upto 6 months', name: '6 months' },
      { label: 'Upto 1 years', name: '1 years' },
      { label: 'Upto 2 years', name: '2 years' },
      { label: 'NA', name: 'NA' },
    ],
  };

  const triggerEvent = ({ eventName, action, property = {} }) => {
    try {
      //trigger segment event
      analyticsTrack({
        objectName: `Act ${eventName.replaceAll('_', ' ')}`,
        actionName: action,
        screen: 'TnC form popup',
        properties: {
          ...property,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });

      // triger lj event
      trackEvent(window.rzpQ.onbr()[action](`act.${eventName}`, { ...property }));
    } catch (error) {
      console.log(error);
    }
  };

  const handleSubmit = async () => {
    triggerEvent({ eventName: 'genrate_tnc_page', action: 'clicked' });

    if (!formValues.support_email) {
      setError('please enter the support email');
      return;
    } else if (!isEmail(formValues.support_email)) {
      setError('enter valid email id');
      return;
    }

    const payload = {
      deliverable_type: isGoodsType ? 'goods' : 'services',
      ...formValues,
    };

    try {
      const res = await merchantFetch({
        url: 'merchant/tnc',
        method: 'post',
        mode: 'live',
        data: payload,
      });
      if (res?.data?.link) {
        const userData = new User({
          ...user,
          merchant_tnc: { ...res.data },
        });

        updateSession({
          user: userData,
          mode,
        });

        triggerEvent({ eventName: 'tnc_link', action: 'success' });
        onCloseModal();
        openModal({
          size: 'medium',
          component: <SuccessTnCGeneratedModal onCloseModal={onCloseModal} data={res.data} />,
        });
      }
    } catch (err) {
      if (err && err.errors.length) {
        triggerEvent({
          eventName: 'tnc_link',
          action: 'failed',
          property: { error_msg: err.errors[0] },
        });
      }
    }
  };

  const handleOnChange = ({ target }) => {
    const name = target.name;
    const value = target.value;
    setFormValues({ ...formValues, [name]: value });
    setError('');
    if (name !== 'support_email') {
      triggerEvent({
        eventName: name,
        action: 'initiated',
        property: { selected_option: value },
      });
    }
  };

  return (
    <div className="gen-tnc-page">
      <ModalHeader
        title="Terms and Conditions Page"
        extraClass="gen-tnc-page__header"
        onCloseClick={onCloseModal}
      />
      <p className="sub-desc">Give us these details to generate your Terms and Conditions page</p>
      <Form>
        <div className="tnc-form">
          <Input.Radio
            className="Input--vTop Input--space"
            label={() => (
              <>
                <span className="info-label">Do you provide goods or services?</span>{' '}
                <span className="help-content">
                  <i className="i i-info-circle" />
                  <Popover
                    align="bottom"
                    theme="dark"
                    followPointer={true}
                    className="tnc-info-popover"
                  >
                    <PopoverBody>
                      <div>
                        Choose goods If you sell/rent any physical products like clothes, groceries,
                        electronics, etc . In all other cases like software, consultancy, donation
                        pages, agencies, service centres, etc, choose services
                      </div>
                    </PopoverBody>
                  </Popover>
                </span>
              </>
            )}
            options={['Goods', 'Services']}
            onChange={({ target }) => {
              setIsGoods(target.value === '0');
              if (target.value !== '0') {
                delete formValues.shipping_period;
                delete formValues.warranty_period;
              } else {
                setFormValues({ ...initialValues, ...formValues });
              }
              triggerEvent({
                eventName: 'deliverable_type',
                action: 'clicked',
                property: { type_of_delivery: target.value === '0' ? 'Goods' : 'Services' },
              });
            }}
          />
          <div>
            <a
              className="btn-link"
              href={`https://${user.isOrgAxis ? 'axis' : 'tnc'}.razorpay.com/tnc/${
                isGoodsType ? '000000000goods' : '000000services'
              }`}
              target="_blank"
              rel="noopener noreferrer"
              onClick={() => triggerEvent({ eventName: 'sample_tnc_page', action: 'clicked' })}
            >
              <span style={{ paddingRight: '4px', fontSize: '12px' }}>View Sample Page</span>{' '}
              <i className="i i-external-link" />
            </a>
          </div>
          <Input
            className="Input--vTop Input--space is-mature"
            label="Support E - Mail"
            name="support_email"
            value={formValues.support_email}
            onChange={handleOnChange}
            description="Mail on which customers can reach out to you for any queries"
            descriptionClass="sub-desc"
            propagatedError={error}
          />
          {isGoodsType && (
            <Input.Select
              name="shipping_period"
              label="How much time do you take to ship orders?"
              className="Input--vTop Input--space"
              options={FIELD_OPTIONS.shipping_period}
              onChange={handleOnChange}
            />
          )}

          <Input.Select
            name="refund_request_period"
            className="Input--vTop Input--space"
            label="Until how many days from order delivery can your customers raise a refund request?"
            onChange={handleOnChange}
            options={FIELD_OPTIONS.refund_period}
          />
          <Input.Select
            name="refund_process_period"
            className="Input--vTop Input--space"
            label="Time taken to process refunds?"
            options={FIELD_OPTIONS.refund_period}
            onChange={handleOnChange}
          />
          {isGoodsType && (
            <Input.Select
              name="warranty_period"
              label={() => (
                <>
                  <span className="info-label">Warranty Period</span>
                  <span className="help-content">
                    <i className="i i-info-circle" />
                    <Popover
                      align="bottom"
                      theme="dark"
                      followPointer={true}
                      className="warranty-popover"
                    >
                      <PopoverBody>
                        <div>
                          In case you sell multiple products with different warranty periods, Choose
                          the range relevant for most of the products
                        </div>
                      </PopoverBody>
                    </Popover>
                  </span>
                </>
              )}
              className="Input--vTop Input--space"
              onChange={handleOnChange}
              options={FIELD_OPTIONS.warranty}
            />
          )}
          <div className="seprator" />
          <div className="declaration">
            <Input.Check
              className="declaration__checkbox"
              defaultValue={isDeclarationCheck ? '1' : '0'}
              onChange={({ target }) => {
                setIsDeclarationCheck(target.value === '1');
                triggerEvent({
                  eventName: 'tnc_consent',
                  action: 'clicked',
                  property: { checked: target.value === '1' },
                });
              }}
            />
            <div className="declaration__text">
              {user.isOrgAxis ? (
                <>
                  I/We have reviewed and accepted the contents of URL LINK which covers the Terms
                  and Conditions as applicable for the Goods & Service being purchased by the
                  customers from me/our Company. I agree and confirm that, these Terms and
                  Conditions are prepared for me/our Company by [Razorpay] basis my/our request and
                  requirement and same shall not be considered as substitute for independent legal
                  advice, in any manner whatsoever. I/We will use my/our independent discretion to
                  determine the suitability of these Terms and Conditions for my/our Company’s
                  businesses purposes. I/We hereby confirm that, neither the [Bank] nor [Razorpay]
                  shall be held liable or responsible in any manner whatsoever in respect of any
                  dispute arising out of or in connection with these Terms and Conditions.
                </>
              ) : (
                <>
                  I / we understand and acknowledge that the use of these draft terms and conditions
                  for my website/ payment page is at my sole discretion and risk. I understand that
                  the provision of these draft terms and conditions by Razorpay is not a substitute
                  for independent legal advice, and I/ we will use our independent discretion to
                  determine the suitability of these for my/ our business purposes and will absolve
                  Razorpay of all liability in this regard.
                </>
              )}
            </div>
          </div>
        </div>
        <div className="tnc-form--btn">
          <Button.Primary
            type="button"
            className="btn-block"
            children="Generate Page"
            onClick={handleSubmit}
            disabled={!isDeclarationCheck}
          />
        </div>
      </Form>
    </div>
  );
};

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
    }),
    { updateSession },
  ),
  RTracking(() => window.rzpQ.component('GenerateTnCPage')),
)(GenerateTnCPage);
