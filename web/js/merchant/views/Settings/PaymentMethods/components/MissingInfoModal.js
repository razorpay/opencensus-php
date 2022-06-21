import React, { useReducer, useEffect } from 'react';
import moment from 'moment';
import Input from 'common/new-ui/Input';
import ModalHeader from 'common/ui/ModalHeader';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { bindActionCreators } from 'redux';
import { closeModal } from 'merchant_common/reducers/modals';
import { withRouter } from 'react-router-dom';
import { merchantFetch } from 'merchant/utils/ajax';

const SET_VALUE = 'SET_VALUE';
const SET_PAGE = 'SET_PAGE';
const SET_SECONDARY_PAGE = 'SET_SECONDARY_PAGE';
const SET_DISABLED = 'SET_DISABLED';

const MissingInfoForm = ({ fields, values, onChange }) => (
  <div className="form-container">
    {fields?.map(({ display_name, name, type, placeholder, format }) => {
      return (
        <div className="form-container-item" key={name}>
          <label htmlFor={name}>{display_name}</label>
          {type.toLowerCase() === 'date' ? (
            <Input.ToCalendar
              autoRender
              data-name="date"
              placeholder="YYYY-MM-DD"
              value={values[name] || moment().format('YYYY-MM-DD')}
              disabled={false}
              readOnly
              onChange={(date) => onChange(name, date, type)}
              size="half"
              addonAfter={<i className="i i-date-range" />}
              placement="topLeft"
              allowToday={false}
              allowedPastTill={1900}
              required
              className="missing-info-form"
            />
          ) : (
            <input
              type={type}
              name={name}
              pattern={format}
              placeholder={placeholder}
              onChange={(e) => onChange(name, e.target.value, type)}
              value={values[name] || ''}
            />
          )}
        </div>
      );
    })}
  </div>
);

const InstrumentTat = ({ tat, instrument }) => (
  <div className="tat">
    <p className="primary-text">
      {instrument} for recurring payments will be enabled for you using{' '}
      <a href="https://razorpay.com/pricing/" target="_blank" rel="noopener noreferrer">
        Standard Pricing <i className="i i-external-link" />
      </a>
      . Enabling the request roughly takes <b>{tat} working days</b>.
    </p>
    <p className="secondary-text">
      Please review the form before submitting. For any concern after submission, you can contact
      our support.
    </p>
  </div>
);

const reducer = (state, action) => {
  switch (action.type) {
    case SET_VALUE:
      return { ...state, values: action.payload };
    case SET_SECONDARY_PAGE: {
      const current = state.page;
      return { ...state, page: current === 2 ? 1 : 1 };
    }
    case SET_PAGE: {
      const current = state.page;
      return { ...state, page: current === 1 ? 2 : 2 };
    }
    case SET_DISABLED:
      return { ...state, disabled: action.payload };
    default:
      return state;
  }
};

const MissingInfoModal = (props) => {
  const [state, dispatch] = useReducer(reducer, { page: 1, values: {}, disabled: true });
  const {
    tat,
    instrument: { name, collect_info, path },
    onCloseClick,
    user,
    closeModal,
    showNotification,
    createRequestAction,
    history,
  } = props;
  const { page, values, disabled } = state;

  const saveMerchantDetails = (data) => {
    return merchantFetch({
      url: `terminals/proxy/collect_info/merchant/details`,
      method: 'post',
      data,
    }).then(async (d) => {
      if (d?.success) {
        await createRequestAction();
      }
      return false;
    });
  };

  const refreshEntries = () => {
    closeModal();
    history.replace('/');
    setTimeout(() => {
      history.replace('/payment-methods');
    }, 10);
  };

  const onSecondaryClick = () => {
    return dispatch({ type: SET_SECONDARY_PAGE });
  };
  const onPrimaryButtonClick = async () => {
    dispatch({ type: SET_PAGE });
    try {
      if (page === 2) {
        dispatch({ type: SET_DISABLED, payload: true });
        const data = {
          ...values,
          instruments: [path],
        };
        await saveMerchantDetails(data, user.id);
        return refreshEntries();
      }
    } catch (error) {
      refreshEntries();
      return showNotification({
        type: 'error',
        message: error?.errors[0],
      });
    } finally {
      dispatch({ type: SET_DISABLED, payload: false });
    }
    return true;
  };

  const onChange = (name, value, type) => {
    const updatedValue = type === 'date' ? moment(value).format('YYYY-MM-DD') : value;
    return dispatch({
      type: SET_VALUE,
      payload: {
        ...state.values,
        [name]: updatedValue,
      },
    });
  };

  useEffect(() => {
    if (Object.values(values)?.every((v) => v.length > 0)) {
      dispatch({ type: SET_DISABLED, payload: false });
    } else {
      dispatch({ type: SET_DISABLED, payload: true });
    }
  }, [values]);

  useEffect(() => {
    const initialValues = {};
    collect_info?.forEach(({ name }) => (initialValues[name] = ''));
    dispatch({ type: SET_VALUE, payload: initialValues });
  }, []);

  return (
    <>
      <div className="header">
        <ModalHeader title="Additional Details required" onCloseClick={onCloseClick} />
      </div>
      {page === 1 && (
        <div className="disclaimer">
          <i className="i i-info-outline" />
          <p>
            Our banking partners need some additional information to proceed with the request.
            Ensure that it matches with previously submitted information to increase chances of your
            request getting fulfilled
          </p>
        </div>
      )}
      {page === 1 ? (
        <MissingInfoForm values={values} fields={collect_info} onChange={onChange} />
      ) : (
        <InstrumentTat tat={tat} instrument={name} />
      )}
      <div className="footer">
        {page === 2 && (
          <button
            type="button"
            disabled={disabled}
            className="btn btn-secondary"
            onClick={onSecondaryClick}
          >
            Back
          </button>
        )}
        <button
          type="button"
          disabled={disabled}
          className="btn btn-primary"
          onClick={onPrimaryButtonClick}
        >
          {page === 1 ? 'Next' : 'Submit Request'} <i className="i i-chevron-right" />
        </button>
      </div>
    </>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
      closeModal,
    },
    dispatch,
  );
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(MissingInfoModal));
