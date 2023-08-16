import axios from 'axios';
import { AsyncBtn } from 'common/new-ui/Button';
import InputField from 'common/ui/Forms/InputField';
import { RadioGroup } from 'common/ui/Forms/RadioGroup';
import { email as validateEmail, phone as validatePhone } from 'common/utils/validators';
import isEmpty from 'lodash/isEmpty';
import { showNotification } from 'merchant_common/reducers/notifications';
import React from 'react';
import { connect } from 'react-redux';
import { Field, formValueSelector, reduxForm } from 'redux-form';
import { getCookie } from 'common/utils/cookies';

const EMAIL = 'email';
const NAME = 'full_name';
const PHONE = 'phone';
const INCHARGE = 'are_you_in_charge_of_managing_payroll_in_your_company_';
const EMPLOYEES = 'how_many_employees_does_your_company_have_';
const COMPANY = 'company';
const OTHER_EMAIL = 'email_of_the_person_in_charge_of_payroll';

const fields = [EMAIL, NAME, PHONE, EMPLOYEES, COMPANY, INCHARGE];

const formMap = {
  'announcement-Nov20-Opfin-NitroV3-cta1': {
    portalId: '5558946',
    formId: '6f7f348b-c318-48e1-bd08-b5f986c8fc2c',
    trackingFormId: 'MoonshineV3',
  },
  'announcement-Nov20-Opfin-NitroV4-cta1': {
    portalId: '5558946',
    formId: '2b883e1b-b6b1-446b-8c58-3ea195692678',
    trackingFormId: 'MoonshineV4',
  },
};

const selector = formValueSelector('customerDetails');

const SubmissionSuccessfull = () => (
  <div className="success-message">
    <h3>Congratulations! You've taken the first step towards automating Payroll.</h3>
    <p>Our product experts will be reaching out to you shortly to schedule your free demo.</p>
  </div>
);

@connect(
  (state) => {
    return {
      ...fields.reduce(
        (acc, element) => ({
          ...acc,
          [element]: selector(state, element),
        }),
        {},
      ),
      initialValues: {
        [NAME]: (state.session.user.user || {}).name,
        [EMAIL]: (state.session.user.user || {}).email,
        [PHONE]: (state.session.user.user || {}).contact_mobile,
      },
    };
  },
  {
    showNotification,
  },
)
@reduxForm({
  form: 'customerDetails',
})
class OpfinAnnouncementForm extends React.Component {
  state = {
    activeView: 'form-view',
  };

  trackCTAClick = (status) => {
    this.props.tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('merchant_dashboard.click_form_cta1', {
        cta_text: 'Schedule Free Demo',
        pageUrl: window.location.href,
        formId: formMap[this.props.id].trackingFormId,
        status,
      }),
    );
  };

  save = (formData) => {
    const { id } = this.props;

    if (this.props[INCHARGE] === 'No') fields.push(OTHER_EMAIL);

    return axios({
      method: 'post',
      baseURL: `https://api.hsforms.com/submissions/v3/integration/submit/${formMap[id].portalId}/${formMap[id].formId}`,
      headers: {
        'Content-Type': 'application/json',
      },
      data: {
        fields: fields.map((field) => ({
          name: field,
          value: formData[field],
        })),
        context: {
          hutk: getCookie('hubspotutk'),
          pageUri: window.location.href,
          pageName: document.title,
        },
      },
    })
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Recorded your data',
          hidePrevious: true,
        });

        this.setState({ activeView: 'submission-success-view' });

        this.trackCTAClick('success');
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.message,
        });

        this.trackCTAClick(err.message);
      });
  };

  render() {
    const { handleSubmit } = this.props;
    const shouldSubmitBeDisabled = fields.some((field) => isEmpty(this.props[field]));

    if (this.state.activeView === 'submission-success-view') return <SubmissionSuccessfull />;

    return (
      <div className="hbspt-opfin-nitro-form">
        <h1>Schedule a free demo to get started</h1>
        <p>
          Fill in your details and our product experts will get in touch with you. Your offer will
          auto-apply once you get started with Opfin.
        </p>
        <form autoComplete="off">
          <div class="form-group">
            <label className="control-label label-required">Email</label>
            <Field
              name={EMAIL}
              component={InputField}
              class="form-control"
              validate={validateEmail('Please provide a valid email')}
              onBlur={this.props.onBlur}
              required
              autoFocus
            />
          </div>
          <div class="form-group">
            <label className="control-label label-required">Full name</label>
            <div>
              <Field
                name={NAME}
                component={InputField}
                class="form-control"
                onBlur={this.props.onBlur}
                required
              />
            </div>
          </div>
          <div class="form-group">
            <label className="control-label label-required">Phone number</label>
            <div>
              <Field
                name={PHONE}
                component={InputField}
                class="form-control"
                onBlur={this.props.onBlur}
                validate={validatePhone('Please provide a valid phone')}
                required
              />
            </div>
          </div>
          <label className="control-label label-required">
            Are you incharge of managing Payroll in your company?
          </label>
          <Field
            component={RadioGroup}
            name={INCHARGE}
            required
            options={[
              { title: 'Yes', value: 'Yes' },
              { title: 'No', value: 'No' },
            ]}
          />
          {this.props[INCHARGE] === 'No' ? (
            <div class="form-group">
              <label className="control-label">Email of the person in charge of payroll</label>
              <Field
                name={OTHER_EMAIL}
                component={InputField}
                class="form-control"
                validate={validateEmail('Please provide a valid email')}
                onBlur={this.props.onBlur}
              />
            </div>
          ) : null}
          <div class="form-group">
            <label className="control-label label-required">Company name</label>
            <Field
              name={COMPANY}
              component={InputField}
              class="form-control"
              onBlur={this.props.onBlur}
              required
            />
          </div>
          <div class="form-group">
            <label className="control-label label-required">
              How many employees does your company have?
            </label>
            <Field
              class="form-control"
              name={EMPLOYEES}
              component="select"
              defaultValue="Please Select"
            >
              {[
                'Please Select',
                '0-10',
                '11-20',
                '21-50',
                '51 to 100',
                '100-250',
                '251-500',
                '500+',
              ].map((l, idx) => (
                <option
                  key={l}
                  value={idx === 0 ? '' : l}
                  selected={idx === 0}
                  disabled={idx === 0}
                >
                  {l}
                </option>
              ))}
            </Field>
          </div>
          <div class="footer">
            <AsyncBtn.Primary
              type="submit"
              class="btn btn-primary"
              disabled={shouldSubmitBeDisabled}
              onClick={handleSubmit(this.save)}
            >
              Schedule Free Demo
            </AsyncBtn.Primary>
          </div>
        </form>
      </div>
    );
  }
}

export default OpfinAnnouncementForm;
