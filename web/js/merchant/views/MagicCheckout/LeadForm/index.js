import { useState, useReducer } from 'react';
import axios from 'axios';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import ModalHeader from 'common/ui/ModalHeader';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';
import * as ModalActions from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { createHubspotPayload } from 'merchant/views/MagicCheckout/LeadForm/utils';
import {
  WEBSITE_TECH_STACKS,
  COD_OPTIONS,
  GMV_OPTIONS,
} from 'merchant/views/MagicCheckout/LeadForm/constants';

const initialState = {
  name: '',
  phone: '',
  url: '',
  stack: 'Shopify',
  cod: 'Yes',
  gmv: 'Less than 5 lacs',
};

const reducer = (state, action) => {
  switch (action.type) {
    case 'SET_FIELD_VALUE':
      return { ...state, [action.field]: action.value };
    default:
      return state;
  }
};

const LeadFormModal = ({ closeModal, onSubmit, user, showNotification }) => {
  const [state, dispatch] = useReducer(reducer, initialState);
  const [isPending, setIsPending] = useState(false);

  const { name, phone, url, stack, cod, gmv } = state;

  function setFieldValue(field, value) {
    dispatch({ field, value, type: 'SET_FIELD_VALUE' });
  }

  function sendFormDataToHubspot(e) {
    e.preventDefault();

    /**
     * name is not a required field and phone and url
     * are the only fields without any default values
     */
    if ([phone?.trim(), url?.trim()].includes('')) {
      showNotification({
        type: 'error',
        message: 'Kindly enter the required fields',
        closeTimeout: 2500,
      });
      return;
    }

    setIsPending(true);

    const {
      current: mId,
      user: { email },
    } = user;

    const payload = createHubspotPayload({
      name,
      email,
      phone,
      url,
      mId,
      stack,
      cod,
      gmv,
    });

    axios({
      method: 'post',
      baseURL:
        'https://api.hsforms.com/submissions/v3/integration/submit/5558946/ce788171-a866-4dbc-8623-e29d3bcddc4b',
      headers: {
        'Content-type': 'application/json',
      },
      data: payload,
    })
      .then(() => {
        onSubmit && onSubmit();
      })
      .catch(() => {
        showNotification({
          type: 'error',
          message: 'We are facing issues submitting your request. Please try again later.',
          closeTimeout: 2500,
        });
      })
      .finally(() => {
        setIsPending(false);
        closeModal();
      });
  }

  const handleClose = () => {
    if (isPending) {
      return;
    }

    closeModal();
  };

  return (
    <>
      <ModalHeader onCloseClick={handleClose} title="Submit details" />
      <div className="fields-container">
        <div>
          <span className="label">First name</span>
          <Input
            value={name}
            onChange={(e) => setFieldValue('name', e.target.value)}
            id="firstname"
            data-testid="firstname"
            className="margin-t-4"
            required={false}
          />
        </div>
        <div>
          <span className="label required">Phone number</span>
          <Input
            value={phone}
            onChange={(e) => setFieldValue('phone', e.target.value)}
            id="phone"
            data-testid="phone"
            className="margin-t-4"
            required
          />
        </div>
        <div>
          <span className="label required">Website URL</span>
          <Input
            value={url}
            onChange={(e) => setFieldValue('url', e.target.value)}
            id="url"
            data-testid="url"
            className="margin-t-4"
            required
          />
        </div>
        <div>
          <span className="label required">What tech stack is your website built on?</span>
          <Input.Radio
            name="stack"
            key="stack"
            data-testid="stack"
            defaultValue="Shopify"
            onChange={(e) => setFieldValue('stack', e.target.value)}
            options={WEBSITE_TECH_STACKS}
            className="margin-t-4"
          />
        </div>
        <div>
          <span className="label required">Do you offer COD?</span>
          <Input.Radio
            name="cod"
            key="cod"
            data-testid="cod"
            defaultValue="Yes"
            onChange={(e) => setFieldValue('cod', e.target.value)}
            options={COD_OPTIONS}
            className="margin-t-4"
          />
        </div>
        <div>
          <span className="label required">Average monthly sales from your website/app</span>
          <Input.Radio
            name="gmv"
            key="gmv"
            data-testid="gmv"
            defaultValue="Less than 5 lacs"
            onChange={(e) => setFieldValue('gmv', e.target.value)}
            options={GMV_OPTIONS}
            className="margin-t-4"
          />
        </div>
      </div>
      <div className="form-submit-cta">
        <AsyncBtn.Primary
          type="button"
          isPending={isPending}
          className="btn btn-primary"
          onClick={sendFormDataToHubspot}
          showLoader={isPending}
        >
          Submit
        </AsyncBtn.Primary>
      </div>
    </>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(LeadFormModal);
