import { useContext, useEffect, useRef } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';
import { useFormikContext } from 'formik';
import { formContext } from './FormContext';

//Redux actions
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { setFormData } from 'merchant/reducers/apmForm/actions';
import { getPurposeCodes } from 'merchant/reducers/profile';
import { showNotification } from 'merchant_common/reducers/notifications';

//Helper functions and constants
import { saveForm } from './services';
import { INSTRUMENTS, OWNER_DETAILS, tabs } from './constants';
import { LOADING } from 'merchant/components/Activation/Constants';
import {
  initializeForm,
  transformApiBody,
  transformPurposeCodeList,
  refreshEntries,
} from './utils';

//Analytics
import {
  trackDataSaveError,
  trackDataSaveSuccess,
  trackDataSaving,
  trackFormButtonClicked,
  trackInstrumentsRequested,
  trackModalClosed,
  trackModalOpened,
} from './analytics';

//Components
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Spinner from 'common/ui/Spinner';
import Button from 'common/new-ui/Button';
import Loader from 'merchant/components/Activation/components/Loader';

//Styles
import './ApmOnboarding.styl';

const ModalContainer = ({
  closeModal,
  formData,
  setFormData,
  isFormLoading,
  showNotification,
  history,
  ...props
}) => {
  const {
    selectedTab,
    onTabClick,
    initialValues,
    setInitialValues,
    activeOwner,
    documents,
    setIsPurposecodeSpecial,
    setDocuments,
    isLoading,
    setLoading,
    setIsUneditable,
    setPurposeCode,
    purposeCode,
    ownerCount,
    setOwnerCount,
  } = useContext(formContext);
  const { errors, values, dirty, validateForm } = useFormikContext();
  const containerRef = useRef(null);

  const CurrentTab = tabs[selectedTab].component;
  const tabName = tabs[selectedTab].name;
  const isTabValid = !errors?.[tabs[selectedTab].dataKey];
  const isOwnerInitialPage = ownerCount > 0 && selectedTab === 2;

  //common function to reinitialize form values to reset dirty
  const reinitializeValues = (data = values) => {
    setInitialValues({ ...data });
    setTimeout(() => validateForm(), 100);
  };

  /**
   * 1. Calls transformApiBody function to change the data to accepted api body
   * 2. Calls saveForm api with the transformedData
   * 3. Gets the id of owner from api response to update it for the current active
   * owner(this is for the case when new owner is saved), updating id makes sure that another
   * save form call with the same owner wont create the owner again
   * 4. Reinitialize new values
   * @param {*} data = values
   * @param {*} isSubmitted = false
   * @returns {boolean} return boolean
   */
  const saveData = async (data = values, isSubmitted = false) => {
    try {
      trackDataSaving(true, tabName, isSubmitted);
      setLoading(LOADING.PENDING);
      const body = transformApiBody(values, errors, documents, activeOwner, isSubmitted);
      const ownerId = await saveForm(body, setFormData);
      if (ownerId) data[OWNER_DETAILS][activeOwner].id = ownerId;
      reinitializeValues(data);
      setLoading(LOADING.SUCCESS);
      trackDataSaveSuccess(tabName, data?.[OWNER_DETAILS], isSubmitted);
    } catch (error) {
      showNotification({
        type: 'error',
        message: error?.errors,
      });
      setLoading(LOADING.ERROR);
      trackDataSaveError(tabName, isSubmitted, error?.errors);
      return false;
    } finally {
      setTimeout(() => setLoading(LOADING.INITIAL), 4000);
    }
    return true;
  };

  /**
   * 1. Let the merchant change tab if:
   * -> current tab form is valid and complete
   * -> tab to move to is adjacent to current tab
   * 2. Save data of current tab if unsaved changes are there and form is valid
   */
  const tabClickHandler = async ({ target }) => {
    const tabIndex = parseInt(target.dataset.index, 10);
    if (dirty && isTabValid) {
      const response = await saveData();
      if (!response) return null;
    }
    if (isTabValid && (tabIndex === selectedTab + 1 || tabIndex === selectedTab - 1)) {
      onTabClick(tabIndex, containerRef);
    }
    if (!dirty && !errors?.[tabs[tabIndex].dataKey] && isTabValid) {
      onTabClick(tabIndex, containerRef);
    }
    return null;
  };

  const onSubmit = () => {
    closeModal();
    refreshEntries(history);
    showNotification({
      type: 'success',
      message: 'Form has been submitted successfully!',
    });
    trackInstrumentsRequested(values?.[INSTRUMENTS]);
    trackModalClosed(true);
  };

  /**
   * 1. Submit form - if current tab is the last tab of form
   * 2. Save & Next - if form is dirty
   * 3. Next - if not dirty
   * @returns {string} returns a string
   */
  const getButtonText = () => {
    if (selectedTab === tabs.length - 1) {
      return 'Submit Form';
    }
    if (dirty || errors?.[tabs[selectedTab].dataKey]) {
      return 'Save & Next';
    }
    return 'Next';
  };

  /**
   * 1. Moves to the next tab if current tab is valid
   * 2. Call save data function if form is valid and dirty
   * 3. Call on submit function if merchant is on last tab
   */
  const onSaveAndNext = async () => {
    const isSubmitted = selectedTab === tabs.length - 1;
    if ((dirty && isTabValid) || isSubmitted) {
      const response = await saveData(values, isSubmitted);
      if (response) {
        !isSubmitted && onTabClick(selectedTab + 1, containerRef);
        isSubmitted && onSubmit();
      }
    } else {
      onTabClick(selectedTab + 1, containerRef);
    }
    trackFormButtonClicked(tabName, getButtonText());
  };

  /**
   * 1. Moves to the prev tab if current tab is valid
   * 2. Call save data function if form is valid and dirty
   */
  const onPrev = async () => {
    if (dirty && isTabValid) {
      const response = await saveData(values);
      if (response) {
        onTabClick(selectedTab - 1, containerRef);
      }
    }
    if (!dirty && isTabValid) {
      onTabClick(selectedTab - 1, containerRef);
    }
  };

  /**
   * 1. Calls save Data function if form is dirty & valid when closed
   * 2. Close modal in any case
   */
  const onCloseClick = () => {
    if (dirty && isTabValid) {
      saveData();
    }
    closeModal();
    trackModalClosed();
  };

  /**
   * Called to initialize form if values already exists in the backend
   */
  const initialSetterFunction = () => {
    const [values, documents, unEditable, isSpecialPurposecode] = initializeForm(
      initialValues,
      formData,
    );
    reinitializeValues(values);
    setDocuments(documents);
    setIsUneditable(unEditable);
    setIsPurposecodeSpecial(isSpecialPurposecode);
    setOwnerCount(formData?.[OWNER_DETAILS]?.length ?? 0);
    trackModalOpened(values?.[INSTRUMENTS]?.length);
  };

  const fetchPurposeCodeList = async () => {
    try {
      const response = await getPurposeCodes();
      const list = transformPurposeCodeList(response.data);
      setPurposeCode(list);
    } catch (error) {
      closeModal();
      showNotification({
        type: 'error',
        message: error?.errors,
      });
    }
  };

  useEffect(() => {
    fetchPurposeCodeList();
  }, []);

  useEffect(() => {
    !isFormLoading && initialSetterFunction();
  }, [isFormLoading]);

  return (
    <Modal className="apm-form-wrapper Modal-container--Activation--wizard" onClose={onCloseClick}>
      <ModalContent>
        {!isFormLoading && purposeCode.length ? (
          <div className="Wizard Activation--wizard">
            <ModalAsideNav
              title="Instant Bank Transfers Activation Form"
              description={
                <p>
                  Complete and submit the form to accept payments from international instant bank
                  transfer instruments
                </p>
              }
              tabs={tabs.map((tab) => tab.name)}
              tabClickHandler={tabClickHandler}
              activeTab={selectedTab}
              tabsValidity={tabs.map((tab) => !errors?.[tab.dataKey])}
            />
            <div className="modal-body">
              <div className="form-container Form--tabular" ref={containerRef}>
                <div className={`head-wrapper${isTabValid ? ' valid' : ''}`}>
                  {selectedTab !== 0 ? (
                    <Button
                      className="device--mobile btn--back"
                      iconBefore="arrow-back"
                      onClick={onPrev}
                    />
                  ) : null}
                  <div className="title">
                    {isTabValid ? <i className="i-check" /> : null}
                    {tabName}
                  </div>
                  {isOwnerInitialPage ? null : (
                    <div className="description">{tabs[selectedTab].description}</div>
                  )}
                </div>
                <CurrentTab saveData={saveData} showNotification={showNotification} {...props} />
              </div>
              <footer>
                {errors?.[tabs[selectedTab].dataKey] ? (
                  <div className="bottom-notice">
                    <i className="i i-info-outline" />
                    <p>{tabs[selectedTab].errorMessage[isOwnerInitialPage ? 1 : 0]}</p>
                  </div>
                ) : null}
                <div className="left">
                  {isLoading !== LOADING.INITIAL ? <Loader isSaving={isLoading} /> : null}
                </div>
                <div className="button-list">
                  <Button.Primary
                    iconAfter="chevron-right device--desktop"
                    disabled={errors?.[tabs[selectedTab].dataKey] || isLoading === LOADING.PENDING}
                    onClick={onSaveAndNext}
                  >
                    {getButtonText()}
                  </Button.Primary>
                </div>
              </footer>
            </div>
          </div>
        ) : (
          <Spinner />
        )}
      </ModalContent>
    </Modal>
  );
};

const mapStateToProps = (state) => ({
  formData: state.apmForm.data,
  isFormLoading: state.apmForm.isLoading,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal, openModal, showNotification, setFormData }, dispatch);
};

export default compose(connect(mapStateToProps, mapDispatchToProps))(withRouter(ModalContainer));
