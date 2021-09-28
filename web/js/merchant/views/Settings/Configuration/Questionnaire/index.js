import React, { useEffect, useReducer } from 'react';
import { Formik, Form } from 'formik';
import { connect } from 'react-redux';

import { merchantFetch } from 'merchant/utils/ajax';
import Spinner from 'common/ui/Spinner';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';
import Loader from 'merchant/components/Activation/components/Loader';
import { LOADING } from 'merchant/components/Activation/Constants';
import { showNotification } from 'merchant_common/reducers/notifications';
import SuccessModal from './SuccessModal';
import ExitConfirmation from './ExitConfirmation';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { initialState, reducer } from './stateHelpers';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';
import {
  tabsData,
  schema,
  fieldToTabMap,
  modelFormData,
  getProductValue,
  modelFormDataBeforeSave,
} from './utils';

const SCREEN = window.location.pathname.includes('payment-methods') ? 'payment methods' : 'config';

// eslint-disable-next-line no-shadow
const Questionnaire = ({ closeModal, openModal, showNotification, triggerSource }) => {
  const [state, dispatch] = useReducer(reducer, initialState);
  const { activeTab, isLoading, isSavingForm, initialValues, tabsValidity } = state;
  let loaderTimeout;
  const isDisabled = false;

  useEffect(() => {
    dispatch({ type: 'LOADING', payload: true });
    if (triggerSource) {
      dispatch({ type: 'TRIGGER_SOURCE', payload: triggerSource });
    }
    merchantFetch('international_enablement')
      .then((res) => {
        // handle empty data here
        if (
          (typeof res.data === 'object' && Object.keys(res.data).length) ||
          (Array.isArray(res.data) && res.data.length)
        ) {
          const data = modelFormData(res.data);
          if (triggerSource) {
            data.products = getProductValue(triggerSource);
          }
          dispatch({ type: 'FORM_INITIAL_VALUES', payload: data });
        }
        dispatch({ type: 'LOADING', payload: false });
      })
      .catch((err) => {
        if (err.status_code === 400) {
          dispatch({ type: 'LOADING', payload: false });
        }
      });
    analyticsTrack({
      objectName: 'intl enablement form',
      actionName: 'open',
      screen: SCREEN,
      properties: {
        timestamp: Date.now(),
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    return () => {
      window.clearTimeout(loaderTimeout); // cleanup
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const validateTab = (formikProps, validateAll, tabIdx) => {
    const tabVal = [...tabsValidity];
    if (!validateAll) {
      tabVal[tabIdx] = true;
    }
    Object.keys(formikProps.errors).forEach((item) => {
      if (fieldToTabMap[item] === tabIdx) {
        tabVal[fieldToTabMap[item]] = false;
      }
    });
    // check required files
    if (
      formikProps.values.accepts_intl_txns === 'true' &&
      !formikProps.values.documents.current_payment_partner_settlement_record &&
      !formikProps.values.documents.bank_statement_inward_remittance
    ) {
      tabVal[3] = false;
    }
    tabVal[4] = false; // always false, no field on this tab
    dispatch({ type: 'TAB_VALIDITY', payload: tabVal });
  };

  const transformErrorsFromAPI = (errors, switchTab, validateTabb) => {
    const tabVal = [true, true, true, true, false];
    let tabToSwitch = Infinity;

    if (errors.business_txn_size_min || errors.business_txn_size_max) {
      errors.business_txn_size = `${errors.business_txn_size_min} ${errors.business_txn_size_max}`;
      delete errors.business_txn_size_min;
      delete errors.business_txn_size_max;
    }
    if (errors.business_txn_size_min || errors.business_txn_size_max) {
      errors.business_txn_size = `${errors.business_txn_size_min} ${errors.business_txn_size_max}`;
      delete errors.business_txn_size_min;
      delete errors.business_txn_size_max;
    }

    Object.keys(errors).forEach((e) => {
      if (e !== 'documents') {
        tabVal[fieldToTabMap[e]] = false;

        errors[e] = errors[e].toString();
      }
      tabToSwitch = Math.min(fieldToTabMap[e], tabToSwitch);
    });

    if (validateTabb) dispatch({ type: 'TAB_VALIDITY', payload: tabVal });

    if (tabToSwitch !== Infinity && switchTab) {
      dispatch({ type: 'ACTIVE_TAB', payload: tabToSwitch });
    }

    return errors;
  };

  const removeLoader = (delay) => {
    loaderTimeout = setTimeout(() => {
      dispatch({ type: 'IS_SAVING_FORM', payload: LOADING.INITIAL });
    }, delay || 7000); // Success states can be removed in 3sec.
  };

  const saveFormData = (formikProps, skipDirtyCheck) => {
    // save only if dirty
    if (!skipDirtyCheck && (!formikProps.dirty || isDisabled)) {
      return null;
    }
    validateTab(formikProps, false, activeTab);
    dispatch({ type: 'IS_SAVING_FORM', payload: LOADING.PENDING });
    window.clearTimeout(loaderTimeout); // Reset the previous removeLoader-call timer on each new Pending
    let formData = {};

    // remove fields whose validation failed and removing files & fileInput-* fields
    Object.keys(formikProps.values).forEach((item) => {
      if (!formikProps.errors[item] && item !== 'files' && item.indexOf('fileInput-') === -1) {
        formData[item] = formikProps.values[item];
      }
    });

    formData = modelFormDataBeforeSave(formData);

    return merchantFetch({
      url: 'international_enablement/draft',
      method: 'post',
      data: formData,
    })
      .then((res) => {
        dispatch({ type: 'IS_SAVING_FORM', payload: LOADING.SUCCESS });
        removeLoader(3000);
        // save the data back to formik
        const data = modelFormData(res.data);
        formikProps.setValues(data);
      })
      .catch((err) => {
        dispatch({ type: 'IS_SAVING_FORM', payload: LOADING.ERROR });
        removeLoader();

        // handle any errors sent from server
        if (err?.errors?._internal) {
          const errorObj = err.errors._internal;
          delete errorObj.internal_error_code;

          // Handling product errors
          const productErr = Object.keys(errorObj).filter((i) => i.includes('product'));
          if (productErr.length) {
            showNotification({
              type: 'error',
              message: errorObj[productErr],
            });
          } else if (err.errors._internal.documents) {
            // Handling document related error
            const errors = Object.keys(err.errors._internal.documents).map((item) =>
              err.errors._internal.documents[item].toString(),
            );

            showNotification({
              type: 'error',
              message: errors,
            });
          } else {
            // Handling form field errors
            const errors = transformErrorsFromAPI(err.errors._internal, false);
            formikProps.setStatus(errors);
          }
        } else {
          // handle other errors
          showNotification({
            type: 'error',
            message: err.errors,
          });
        }
      });
  };

  const submitForm = (formData, bag) => {
    formData = modelFormDataBeforeSave(formData);
    bag.setStatus(null); // reset status
    analyticsTrack({
      objectName: 'intl enablement form',
      actionName: 'submit',
      screen: SCREEN,
      properties: {
        timestamp: Date.now(),
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    merchantFetch({ url: 'international_enablement/submit', method: 'post', data: formData })
      .then(() => {
        analyticsTrack({
          objectName: 'intl enablement form',
          actionName: 'submit success',
          screen: SCREEN,
          properties: {
            timestamp: Date.now(),
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        // close this modal and open success modal
        closeModal();
        openModal({ component: <SuccessModal closeModal={closeModal} /> });
      })
      .catch((err) => {
        // handle any errors sent from server
        if (err.errors._internal) {
          const errorObj = err.errors._internal;
          delete errorObj.internal_error_code;

          // Handling product errors
          if (errorObj.products) {
            showNotification({
              type: 'error',
              message: errorObj.products,
            });
          } else if (err.errors._internal.documents) {
            // Handling document related error
            const errors = Object.keys(err.errors._internal.documents).map((item) =>
              err.errors._internal.documents[item].toString(),
            );

            showNotification({
              type: 'error',
              message: errors,
            });
          } else {
            // Handling form field errors
            const errors = transformErrorsFromAPI(err.errors._internal, true, true);
            bag.setStatus(errors);
          }
        } else {
          // handle other errors
          showNotification({
            type: 'error',
            message: err.errors,
          });
        }
      });
  };

  const handleOnSubmit = (e, formikProps) => {
    e.preventDefault();
    formikProps.validateForm().then((err) => {
      console.error('errors', err);
      // set tabs validity. Last tab is set to false since it doesn't contain any field (submit form)
      const tabVal = [true, true, true, true, false];
      Object.keys(err).forEach((item) => {
        tabVal[fieldToTabMap[item]] = false;
      });
      dispatch({ type: 'TAB_VALIDITY', payload: tabVal });
      if (Object.keys(err).length) {
        showNotification({
          type: 'error',
          message: 'Please fix the errors',
        });
      } else if (
        formikProps.values.accepts_intl_txns === 'true' &&
        !formikProps.values.documents.current_payment_partner_settlement_record &&
        !formikProps.values.documents.bank_statement_inward_remittance
      ) {
        showNotification({
          type: 'error',
          message: 'Please upload required documents',
          closeTimeout: 7000,
        });
        dispatch({ type: 'ACTIVE_TAB', payload: 3 });
        tabsValidity[3] = false;
        dispatch({ type: 'TAB_VALIDITY', payload: tabsValidity });
      }
    });
    formikProps.handleSubmit();
  };

  const handleNext = (formikProps) => {
    analyticsTrack({
      objectName: 'intl enablement form',
      actionName: `click Save & Next on ${tabsData[activeTab]?.name}`,
      screen: SCREEN,
      properties: {
        timestamp: Date.now(),
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    validateTab(formikProps, false, activeTab);
    if (activeTab < tabsData.length - 1) dispatch({ type: 'NEXT_TAB' });
    saveFormData(formikProps);
  };

  const handlePrev = () => {
    if (activeTab > 0) dispatch({ type: 'PREV_TAB' });
  };

  const tabClickHandler = ({ target }, formikProps) => {
    validateTab(formikProps, false, activeTab);
    const currentTab = Number(target.dataset.index);
    dispatch({ type: 'ACTIVE_TAB', payload: currentTab });
    saveFormData(formikProps);
  };

  const closeQuestionnaire = (formikProps) => {
    closeModal();
    if (formikProps.dirty) {
      openModal({
        component: (
          <ExitConfirmation
            triggerSource={triggerSource}
            saveFormData={() => saveFormData(formikProps)}
          />
        ),
        size: 'small',
      });
    } else {
      analyticsTrack({
        objectName: 'intl enablement form',
        actionName: `click close`,
        screen: SCREEN,
        properties: {
          timestamp: Date.now(),
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  return (
    <Formik
      initialValues={initialValues}
      enableReinitialize={true}
      validationSchema={schema}
      onSubmit={(values, bag) => {
        submitForm(values, bag);
        bag.setSubmitting(false);
      }}
    >
      {(formikProps) => {
        return (
          <Modal onClose={() => closeQuestionnaire(formikProps)} className="intl-questionnaire">
            <ModalContent>
              {isLoading ? (
                <Spinner center />
              ) : (
                <div class="Wizard">
                  <ModalAsideNav
                    title="International activation form"
                    description={<p>Complete and submit the form to accept payments</p>}
                    tabs={tabsData.map((tab) => tab.name)}
                    tabClickHandler={(e) => tabClickHandler(e, formikProps)}
                    activeTab={activeTab}
                    tabsValidity={tabsValidity}
                  />
                  <Form
                    onChange={formikProps.handleChange}
                    onSubmit={(e) => handleOnSubmit(e, formikProps)}
                  >
                    <main className="form-container">
                      {React.cloneElement(tabsData[activeTab].component, {
                        triggerSource,
                        disabled: isDisabled,
                        saveFormData,
                      })}
                    </main>
                    <footer>
                      <div class="left">
                        {isSavingForm !== null ? <Loader isSaving={isSavingForm} /> : ''}
                      </div>
                      {activeTab > 0 && (
                        <Button type="button" onClick={handlePrev}>
                          Previous
                        </Button>
                      )}

                      {activeTab === tabsData.length - 1 ? (
                        <Button.Primary
                          type="submit"
                          iconAfter="chevron-right"
                          disabled={
                            formikProps.isSubmitting ||
                            isDisabled ||
                            Object.keys(formikProps.errors).length
                          }
                        >
                          Submit & Verify
                        </Button.Primary>
                      ) : (
                        <Button.Primary
                          type="button"
                          onClick={() => handleNext(formikProps)}
                          iconAfter="chevron-right"
                        >
                          Next
                        </Button.Primary>
                      )}
                    </footer>
                  </Form>
                </div>
              )}
            </ModalContent>
          </Modal>
        );
      }}
    </Formik>
  );
};

export default connect(null, {
  showNotification,
  openModal: openModalFn,
  closeModal: closeModalFn,
})(Questionnaire);
