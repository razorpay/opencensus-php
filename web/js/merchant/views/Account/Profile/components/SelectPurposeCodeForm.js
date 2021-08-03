import { connect } from 'react-redux';
import { useEffect, useReducer } from 'react';
import AsyncButton from 'react-async-button';

import ModalHeader from 'common/ui/ModalHeader';
import Accordion, {
  AccordionItem,
  AccordionItemTitle,
  AccordionItemContent,
} from 'common/ui/Accordion';
import { closeModal } from 'merchant_common/reducers/modals';
import { getPurposeCodes, updatePurposeCode } from 'merchant/reducers/profile';
import { showNotification } from 'merchant_common/reducers/notifications';
import Form from 'common/new-ui/Form';
import Spinner from 'common/ui/Spinner';
import closeImg from '../../../../../../icons/merchant/close_small.svg';
import tickImg from '../../../../../../icons/merchant/tick.svg';
import zoomImg from '../../../../../../icons/merchant/zoom_out_24px.svg';

const initialState = {
  isLoading: true,
  purposeCodeList: [],
  disableSelectButton: true,
  btnClass: 'select-purpose-code-button-disabled',
  confirmMode: false,
  searchText: '',
  showSearchResults: false,
  searchResults: [],
  selectedPurposeCode: null,
  selectedDescription: null,
};

const reducer = (state, action) => {
  switch (action.type) {
    case 'setPurposeCodeList':
      return {
        ...state,
        purposeCodeList: action.payload,
        isLoading: false,
      };
    case 'setSearchText':
      return {
        ...state,
        searchText: action.payload,
      };
    case 'hideSearchResults':
      return {
        ...state,
        showSearchResults: false,
        searchText: '',
        searchResults: [],
      };
    case 'showSearchResults':
      return {
        ...state,
        searchResults: action.payload,
        showSearchResults: true,
      };
    case 'selectPurposeCode':
      return {
        ...state,
        selectedPurposeCode: action.payload.code,
        selectedDescription: action.payload.description,
        btnClass: 'select-purpose-code-button',
        disableSelectButton: false,
      };
    case 'switchToConfirmMode':
      return {
        ...state,
        confirmMode: true,
      };
    case 'switchToSelectMode':
      return {
        ...state,
        confirmMode: false,
      };
  }
};

const SelectPurposeCodeForm = (props) => {
  const [state, dispatch] = useReducer(reducer, initialState);

  useEffect(() => {
    getPurposeCodes()
      .then((res) => {
        if (res.success === true) {
          dispatch({ type: 'setPurposeCodeList', payload: res.data });
        }
      })
      .catch(() => {
        props.showNotification({
          type: 'error',
          message: 'Sorry! Could not fetch purpose codes',
        });
        dispatch({ type: 'setPurposeCodeList', payload: [] });
      });
  }, []);

  const searchTextHandler = (event) => {
    const enteredSearchText = event.target.value;
    dispatch({ type: 'setSearchText', payload: enteredSearchText });
    searchForMatchingCodes(enteredSearchText);
  };

  const searchForMatchingCodes = (text) => {
    const enteredSearchTextLower = text.toLowerCase();
    if (text.trim() === '') {
      dispatch({ type: 'hideSearchResults' });
    } else {
      let tempSearchResults = [];
      state.purposeCodeList.forEach((categoryCodes) => {
        categoryCodes.codes.forEach((code) => {
          if (
            code.purposeCode.toLowerCase().includes(enteredSearchTextLower) ||
            code.description.toLowerCase().includes(enteredSearchTextLower)
          )
            tempSearchResults.push(code);
        });
      });
      dispatch({ type: 'showSearchResults', payload: tempSearchResults });
    }
  };

  const onPurposeCodeClickHandler = (code, description) => {
    dispatch({ type: 'selectPurposeCode', payload: { code: code, description: description } });
  };

  const onConfirmCodeHandler = () => {
    if (state.selectedPurposeCode === null || state.selectedPurposeCode === '') {
      return;
    }
    const data = {
      purpose_code: state.selectedPurposeCode,
    };
    return updatePurposeCode(data)
      .then((res) => {
        if (res.data.success === true) {
          props.showNotification({
            type: 'success',
            message: 'Purpose code updated successfully.',
          });
          props.onSetPurposeCode(state.selectedPurposeCode, state.selectedDescription);
          props.closeModal();
        }
      })
      .catch(() => {
        props.showNotification({
          type: 'error',
          message: 'Sorry! Could not update purpose code',
        });
      });
  };

  /* Displaying options on opening and confirmation modal on 
  selection */
  if (state.confirmMode) {
    return (
      <Form class="purpose-code-confirmation-form">
        <ModalHeader
          title="Confirmation"
          extraClass="purpose-code-modal-heading"
          onCloseClick={() => {
            props.closeModal();
          }}
        />

        <div class="purpose-code-confirmation-content">
          You have selected <b>{state.selectedPurposeCode} </b> - <b>{state.selectedDescription}</b>
          <div class="purpose-code-confirmation-text">
            Make sure you select the correct purpose code for the nature of foreign transactions.
          </div>
        </div>

        <div class="purpose-code-confirmation-button-contain">
          <AsyncButton
            type="button"
            class="btn btn-primary purpose-code-confirmation-select-button"
            text="Confirm Purpose Code"
            pendingText="Updating.."
            onClick={onConfirmCodeHandler}
          />
          <span
            class="purpose-code-confirmation-back-button"
            onClick={() => {
              dispatch({ type: 'switchToSelectMode' });
            }}
          >
            Back
          </span>
        </div>
      </Form>
    );
  } else {
    return (
      <Form>
        <div class="purpose-code-header">
          <ModalHeader
            title="Select Purpose Code"
            extraClass="purpose-code-modal-heading"
            onCloseClick={() => {
              props.closeModal();
            }}
          />

          <div class="purpose-code-search-container">
            <img src={zoomImg} class="purpose-code-search-icon" />
            <input
              type="text"
              placeholder="Search code or Industry"
              name="search_code"
              class="form-control purpose-code-search-input"
              autoFocus={true}
              value={state.searchText}
              onChange={searchTextHandler}
            />
            <img
              src={closeImg}
              class="purpose-code-search-close"
              onClick={() => {
                dispatch({ type: 'hideSearchResults' });
              }}
            />
          </div>
        </div>

        <div class="scroll-codes">
          {/* Container for search results */}
          {state.showSearchResults &&
            (state.searchResults.length === 0 ? (
              <div class="purpose-code-no-results">No results found for '{state.searchText}'</div>
            ) : (
              <div class="purpose-code-accordion-content">
                {state.searchResults.map((code) => (
                  <div
                    class="purpose-code-item"
                    onClick={() => {
                      onPurposeCodeClickHandler(code.purposeCode, code.description);
                    }}
                    key={code.purposeCode}
                  >
                    <div>
                      <b>{code.purposeCode}</b>
                    </div>
                    <div class="purpose-code-desc">{code.description}</div>
                    {state.selectedPurposeCode === code.purposeCode && (
                      <div class="tick-icon-contain">
                        <img src={tickImg} />
                      </div>
                    )}
                  </div>
                ))}
              </div>
            ))}

          <Accordion>
            {state.isLoading ? (
              <div class="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              state.purposeCodeList.map((categoryCodes) => (
                <AccordionItem
                  key={categoryCodes.purposeGroup}
                  className="purpose-code-accordion-item "
                >
                  <AccordionItemTitle className="purpose-code-category">
                    {categoryCodes.purposeGroup}
                  </AccordionItemTitle>
                  <AccordionItemContent className="purpose-code-accordion-content">
                    {categoryCodes.codes.map((code) => (
                      <div
                        class="purpose-code-item"
                        key={code.purposeCode}
                        onClick={() => {
                          onPurposeCodeClickHandler(code.purposeCode, code.description);
                        }}
                      >
                        <div>
                          <b>{code.purposeCode}</b>
                        </div>
                        <div class="purpose-code-desc">{code.description}</div>
                        {state.selectedPurposeCode === code.purposeCode && (
                          <div class="tick-icon-contain">
                            <img src={tickImg} />
                          </div>
                        )}
                      </div>
                    ))}
                  </AccordionItemContent>
                </AccordionItem>
              ))
            )}
          </Accordion>
        </div>

        <div class="select-purpose-code-button-contain">
          <button
            type="button"
            class={`btn btn-primary btn-block ${state.btnClass}`}
            disabled={state.disableSelectButton}
            onClick={() => {
              dispatch({ type: 'switchToConfirmMode' });
            }}
          >
            {state.selectedPurposeCode === null
              ? 'Select Code'
              : `Select ${state.selectedPurposeCode}`}
          </button>
        </div>
      </Form>
    );
  }
};

export default connect(null, { closeModal, showNotification })(SelectPurposeCodeForm);
