import React, { useState } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { closeModal } from 'merchant_common/reducers/modals';
import ModalHeader from 'common/ui/ModalHeader';
import { Button, SearchIcon, Text } from '@razorpay/blade/components';
import debounce from 'common/utils/debounce';

function SettingModal({
  header,
  closeModal,
  variant,
  placeholder,
  searchFn,
  name = '',
  confirmAction,
  className = '',
  itemClassName = '',
  disableConfirmButton = false,
  loading,
  type,
  children,
}) {
  const isLoading = loading[type];
  const [inputError, setInputError] = useState('');
  const [inputVal, setInputVal] = useState(name);
  const debouncedSearchFn = debounce(searchFn, 500);
  const handleChange = (event) => {
    event.preventDefault();
    event.stopPropagation();
    debouncedSearchFn(event.target.value);
  };
  const handleNameChange = (event) => {
    const val = event.target.value;
    if (val === '') {
      setInputError('Required');
    } else {
      setInputError('');
      setInputVal(val);
    }
  };
  const handleClick = () => {
    if (inputVal === '') {
      setInputError('Required');
      return;
    }
    confirmAction(inputVal);
  };
  return (
    <div className={`cod-settings-modal ${className}`}>
      <ModalHeader title={header} extraClass="no-padding" onCloseClick={closeModal} />
      <div className="name-container">
        <Text weight="semibold">{variant} name</Text>
        <div class="name-input">
          <input
            data-testid="name-input"
            onChange={handleNameChange}
            defaultValue={inputVal}
            className={`form-control ${inputError ? 'error' : ''}`}
            placeholder={`Enter ${variant.toLowerCase()} name`}
          />
          {inputError && <p className="name-error">{inputError}</p>}
        </div>
      </div>
      <div className="items-container">
        <Text>Select {placeholder}</Text>
        <div className="input-container">
          <SearchIcon size="large" />
          <input
            data-testid="search-input"
            onChange={handleChange}
            placeholder={`Search ${placeholder}`}
          />
        </div>
        <div className={`items ${itemClassName}`}>{children}</div>
      </div>
      <div className="actions-container">
        <div className="actions-text" />
        <div className="actions">
          <Button
            isDisabled={isLoading || disableConfirmButton}
            onClick={closeModal}
            variant="secondary"
          >
            Cancel
          </Button>
          <Button
            testID="confirm-button"
            isLoading={isLoading}
            isDisabled={isLoading || disableConfirmButton}
            onClick={handleClick}
          >
            Confirm
          </Button>
        </div>
      </div>
    </div>
  );
}

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

const mapStateToProps = (state) => ({
  loading: state.magicCODEngine.loading,
});

export default connect(mapStateToProps, mapDispatchToProps)(SettingModal);
