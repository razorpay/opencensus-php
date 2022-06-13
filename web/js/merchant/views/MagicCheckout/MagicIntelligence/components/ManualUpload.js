import { useCallback, useEffect, useReducer } from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { AsyncBtn } from 'common/new-ui/Button';
import * as ModalActions from 'merchant_common/reducers/modals';
import ManualUploadPreview from 'merchant/views/MagicCheckout/MagicIntelligence/components/ManualUploadPreview';
import { validate } from 'merchant/views/MagicCheckout/MagicIntelligence/validator';
import { ATTRIBUTE_TYPE } from 'merchant/views/MagicCheckout/MagicIntelligence/constants';

const onCreateModal = (
  openModal,
  fetchAll,
  data,
  onModalClose,
  uploadList,
  modalSource,
  resetHandler,
) => {
  const dataPreview = data.slice(0, 10).map((item, index) => ({ ...item, count: index + 1 }));

  openModal({
    size: 'large',
    component: (
      <ManualUploadPreview
        data={data}
        modalSource={modalSource}
        fetchAll={fetchAll}
        uploadList={uploadList}
        onModalClose={onModalClose}
        resetHandler={resetHandler}
        dataPreview={dataPreview}
      />
    ),
  });
};

const initialState = {
  type: '',
  value: '',
  disable: true,
  errorMsg: '',
};

const reducer = (state, action) => {
  switch (action.type) {
    case 'SET_TYPE':
      return { ...state, type: action.value };
    case 'SET_VALUE':
      return { ...state, value: action.value };
    case 'SET_DISABLE':
      return { ...state, disable: action.value };
    case 'SET_ERROR_MSG':
      return { ...state, errorMsg: action.value };
    default:
      return state;
  }
};

const ManualUpload = (props) => {
  const { list, onModalClose, uploadList, fetchAll, modalSource, openModal, resetHandler } = props;

  const [state, dispatch] = useReducer(reducer, initialState);

  const { type, value, errorMsg, disable } = state;

  const setType = (value) => {
    dispatch({ type: 'SET_TYPE', value });
  };

  const setValue = (value) => {
    dispatch({ type: 'SET_VALUE', value });
  };

  const setErrorMsg = (value) => {
    dispatch({ type: 'SET_ERROR_MSG', value });
  };

  const setDisable = (value) => {
    dispatch({ type: 'SET_DISABLE', value });
  };

  useEffect(() => {
    setErrorMsg('');
    if (type.length !== 0 && value.length !== 0) {
      setDisable(false);
    }
  }, [type, value]);

  const checkValidation = useCallback(() => {
    const msg = validate(type, value);
    setErrorMsg(msg);
    setDisable(msg.length !== 0);
  }, [setDisable, setErrorMsg, validate]);

  const onClickUpload = useCallback(() => {
    const values = value.split(',');

    const data = values?.map((val) => ({
      attribute_type: type,
      attribute_value: val.trim(),
    }));

    onCreateModal(openModal, fetchAll, data, onModalClose, uploadList, modalSource, resetHandler);
  }, [openModal, fetchAll, type, value, onModalClose, uploadList, modalSource, resetHandler]);

  return (
    <>
      <form className="form-horizontal manual-upload">
        <div className="modal-header">
          <p>Or enter the items to the list manually</p>
        </div>
        <div className="modal-body">
          <div className="form-group">
            <label className="control-label control-type">Type</label>
            <div className="modal-select">
              <select
                className="form-control"
                onChange={(e) => setType(e.target.value)}
                onBlur={value.length !== 0 ? checkValidation : null}
              >
                <option value="">Select Type</option>
                {list.map((item, index) => (
                  <option value={item} key={index}>
                    {ATTRIBUTE_TYPE[item]}
                  </option>
                ))}
              </select>
            </div>
          </div>
          <div className="form-group">
            <label className="control-label control-value">Value</label>
            <div className="modal-textArea">
              <div className="text-section">
                <textarea
                  className="form-control"
                  placeholder="Enter the comma seperated values"
                  rows="5"
                  type="textArea"
                  onChange={(e) => setValue(e.target.value)}
                  onBlur={checkValidation}
                />
              </div>
              <div className="modal-err-msg">{errorMsg}</div>
            </div>
          </div>
        </div>
        <div className="modal-footer">
          <AsyncBtn type="button" onClick={onModalClose}>
            Cancel
          </AsyncBtn>

          <AsyncBtn.Primary type="button" onClick={onClickUpload} disabled={disable}>
            Confirm
          </AsyncBtn.Primary>
        </div>
      </form>
    </>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      ...ModalActions,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(ManualUpload);
