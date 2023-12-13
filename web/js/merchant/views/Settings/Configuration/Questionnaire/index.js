import React, { useCallback, useEffect, useReducer, useRef } from 'react';
import { IconButton, ArrowLeftIcon } from '@razorpay/blade/components';
import { Formik, Form } from 'formik';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { useSplitzService } from 'common/splitz';
import Spinner from 'common/ui/Spinner';
import useDebounce from 'common/utils/useDebounce';
import { LOADING } from 'merchant/components/Activation/Constants';
import Loader from 'merchant/components/Activation/components/Loader';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import ExitConfirmation from './ExitConfirmation';
import FormWrapper from './FormWrapper';
import SuccessModal from './SuccessModal';
import {
  trackDataSaveError,
  trackDataSaveSuccess,
  trackDataSaving,
  trackFormButtonClicked,
  trackModalClosed,
  trackModalOpened,
} from './analytics';
import { initialState, initialStateForRevamp, reducer } from './stateHelpers';
import {
  tabsData as tabs,
  revampTabs,
  getFormSchema,
  fieldToTabMap,
  modelFormData,
  getProductValue,
  modelFormDataBeforeSave,
  getAdditionalDocumentsBasedOnSubCategory,
} from './utils';

// eslint-disable-next-line no-shadow
const Questionnaire = ({
  closeModal,
  openModal,
  showNotification,
  triggerSource,
  isRevampFlow = false,
  onQuestionnaireSubmitSuccess,
  user,
}) => {
  const initState = isRevampFlow ? initialStateForRevamp : initialState;
  const [state, dispatch] = useReducer(reducer, initState);
  const { activeTab, isLoading, isSavingForm, initialValues, tabsValidity } = state;
  let loaderTimeout;
  const isDisabled = false;
  const tabsData = isRevampFlow ? revampTabs : tabs;
  const {
    abExperiments: { internationalAdditionalDocs },
  } = useSplitzService();

  const isAdditionalDocExperimentEnabled = internationalAdditionalDocs.variables.result === 'on';

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
          const data = modelFormData(res.data, isRevampFlow ? user : undefined);
          if (triggerSource) {
            data.products = getProductValue(triggerSource);
          }
          dispatch({ type: 'FORM_INITIAL_VALUES', payload: data });
        }
        dispatch({ type: 'LOADING', payload: false });
      })
      .catch(() => {
        dispatch({ type: 'LOADING', payload: false });
      });
    trackModalOpened();
    return () => {
      window.clearTimeout(loaderTimeout); // cleanup
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const validateTab = useCallback(
    (formikProps, validateAll, tabIdx) => {
      const tabVal = [...tabsValidity];
      if (!validateAll) {
        tabVal[tabIdx] = true;
      }
      Object.keys(formikProps.errors).forEach((item) => {
        if (fieldToTabMap[item] === tabIdx) {
          tabVal[fieldToTabMap[item]] = false;
        }
      });

      const mandatoryFile =
        user.business_category && user.business_subcategory
          ? getAdditionalDocumentsBasedOnSubCategory(user)
          : null;
      // check required files
      if (
        formikProps.values.accepts_intl_txns === 'true' &&
        !formikProps.values.documents.current_payment_partner_settlement_record
      ) {
        tabVal[2] = false;
      }
      // Additional document validation
      if (mandatoryFile?.name && !formikProps.values.documents.others?.[mandatoryFile.name]) {
        tabVal[2] = false;
      }
      tabVal[3] = false; // always false, no field on this tab
      dispatch({ type: 'TAB_VALIDITY', payload: tabVal });
    },
    [dispatch, tabsValidity],
  );

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

  const requestId = useRef(null);

  const makeFormDataCall = (formData, formikProps) => {
    const requestIdForTheCurrentCall = Math.random();
    requestId.current = requestIdForTheCurrentCall;
    return merchantFetch({
      url: 'international_enablement/draft',
      method: 'post',
      data: formData,
    })
      .then((res) => {
        // Since multiple requests are triggered if user saves the form continously,
        // only the latest response is considered
        if (requestIdForTheCurrentCall === requestId.current || !isRevampFlow) {
          dispatch({ type: 'IS_SAVING_FORM', payload: LOADING.SUCCESS });
          removeLoader(3000);
          // save the data back to formik
          const data = modelFormData(res.data);
          formikProps.setValues(data);
          formikProps.validateForm();
          trackDataSaveSuccess(tabsData?.[activeTab]?.name);
        }
      })
      .catch((err) => {
        if (requestIdForTheCurrentCall === requestId.current || !isRevampFlow) {
          dispatch({ type: 'IS_SAVING_FORM', payload: LOADING.ERROR });
          removeLoader();
          trackDataSaveError(tabsData?.[activeTab]?.name, err?.errors);

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
        }
      });
  };

  const debouncedFormDataCall = useDebounce(makeFormDataCall, 500);

  const saveFormData = (formikProps, skipDirtyCheck) => {
    // save only if dirty
    if (!skipDirtyCheck && (!formikProps.dirty || isDisabled)) {
      return null;
    }
    validateTab(formikProps, false, activeTab);
    trackDataSaving(true, tabsData?.[activeTab]?.name);
    dispatch({ type: 'IS_SAVING_FORM', payload: LOADING.PENDING });
    window.clearTimeout(loaderTimeout); // Reset the previous removeLoader-call timer on each new Pending
    let formData = {};

    // remove fields whose validation failed and removing files & fileInput-* fields
    Object.keys(formikProps.values).forEach((item) => {
      const addField =
        item !== 'files' &&
        item.indexOf('fileInput-') === -1 &&
        (isRevampFlow ? true : !formikProps.errors[item]);
      if (addField) {
        formData[item] = formikProps.values[item];
      }
    });

    formData = modelFormDataBeforeSave(formData);

    if (isRevampFlow) {
      formData.version = 'v2';
      return debouncedFormDataCall(formData, formikProps);
    } else {
      return makeFormDataCall(formData, formikProps);
    }
  };

  const submitForm = (formData, bag) => {
    formData = modelFormDataBeforeSave(formData, isRevampFlow);
    bag.setStatus(null); // reset status
    trackDataSaving(true, tabsData?.[activeTab]?.name, true);
    if (isRevampFlow) {
      formData.version = 'v2';
    }
    merchantFetch({ url: 'international_enablement/submit', method: 'post', data: formData })
      .then(() => {
        trackDataSaveSuccess(tabsData?.[activeTab]?.name, true);
        // close this modal and open success modal
        closeModal();
        if (isRevampFlow && onQuestionnaireSubmitSuccess) {
          onQuestionnaireSubmitSuccess();
        } else {
          openModal({ component: <SuccessModal closeModal={closeModal} /> });
        }
        bag.setSubmitting(false);
      })
      .catch((err) => {
        bag.setSubmitting(false);
        // handle any errors sent from server
        trackDataSaveError(tabsData?.[activeTab]?.name, err?.errors, true);
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
    trackFormButtonClicked(tabsData?.[activeTab]?.name, 'Submit & Verify');
    if (!isRevampFlow) {
      formikProps.validateForm().then((err) => {
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
          !formikProps.values.documents.current_payment_partner_settlement_record
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
    }
    formikProps.handleSubmit();
  };

  const handleNext = (formikProps) => {
    trackFormButtonClicked(
      tabsData?.[activeTab]?.name,
      activeTab === tabsData.length - 1 ? 'Submit & Verify' : 'Next',
    );
    if (!isRevampFlow) {
      validateTab(formikProps, false, activeTab);
    }
    if (activeTab < tabsData.length - 1) dispatch({ type: 'NEXT_TAB' });
    saveFormData(formikProps);
  };

  const handlePrev = () => {
    trackFormButtonClicked(tabsData?.[activeTab]?.name, 'Previous');
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
            activeTab={activeTab}
            saveFormData={() => saveFormData(formikProps)}
            isRevampFlow={isRevampFlow}
          />
        ),
        size: 'small',
      });
    } else {
      trackModalClosed();
    }
  };

  const checkWhetherAcceptTermsError = (formikProps) => {
    return (
      Object.keys(formikProps.errors)?.includes('submit') &&
      Object.keys(formikProps.errors)?.length == 1
    );
  };

  const schema = getFormSchema(
    isRevampFlow,
    isAdditionalDocExperimentEnabled ? user.business_type : null,
  );

  // only consider isNextDisabled in revamp
  const isNextDisabled = !tabsValidity[activeTab] && isRevampFlow;

  return (
    <Formik
      initialValues={initialValues}
      enableReinitialize={true}
      validationSchema={schema}
      onSubmit={(values, bag) => {
        submitForm(values, bag);
      }}
      validateOnMount={isRevampFlow}
    >
      {(formikProps) => {
        return (
          <Modal onClose={() => closeQuestionnaire(formikProps)} className="intl-questionnaire">
            <ModalContent>
              {isLoading ? (
                <Spinner center />
              ) : (
                <div className="Wizard">
                  <ModalAsideNav
                    title="International activation form"
                    description={<p>Complete and submit the form to accept payments</p>}
                    tabs={tabsData.map((tab) => tab.name)}
                    tabClickHandler={(e) => tabClickHandler(e, formikProps)}
                    activeTab={activeTab}
                    tabsValidity={tabsValidity}
                    disableTabCondition={(tabIdx) => tabIdx > activeTab}
                  />
                  <Form
                    onChange={formikProps.handleChange}
                    onSubmit={(e) => handleOnSubmit(e, formikProps)}
                  >
                    <main className="form-container">
                      <div className="go-back-button">
                        {activeTab !== 0 ? (
                          <IconButton
                            icon={ArrowLeftIcon}
                            accessibilityLabel="Go back"
                            onClick={() =>
                              tabClickHandler(
                                { target: { dataset: { index: activeTab - 1 } } },
                                formikProps,
                              )
                            }
                            size="large"
                          />
                        ) : null}
                      </div>
                      <FormWrapper
                        activeTab={activeTab}
                        validateTab={validateTab}
                        isRevampFlow={isRevampFlow}
                      >
                        {React.cloneElement(tabsData[activeTab].component, {
                          triggerSource,
                          disabled: isDisabled,
                          saveFormData,
                          isRevampFlow,
                          closeModal,
                        })}
                      </FormWrapper>
                    </main>
                    <footer>
                      <div className="left">
                        {isSavingForm !== null ? <Loader isSaving={isSavingForm} /> : ''}

                        {/* Show message if all fields are not filled */}
                        {activeTab === tabsData.length - 1 &&
                          isSavingForm == null &&
                          Object.keys(formikProps.errors)?.length > 0 &&
                          (checkWhetherAcceptTermsError(formikProps) ? (
                            ''
                          ) : (
                            <div className="text-danger">
                              You have not answered all the previous mandatory questions
                            </div>
                          ))}
                      </div>
                      <div>
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
                            disabled={isNextDisabled}
                          >
                            Next
                          </Button.Primary>
                        )}
                      </div>
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

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  showNotification,
  openModal: openModalFn,
  closeModal: closeModalFn,
})(Questionnaire);
