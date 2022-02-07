import React, { useEffect, useState } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import FileUpload from 'merchant/components/File/Upload';
import { connect } from 'react-redux';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  getIirDiscrepancies,
  fetchMerchantInstruments,
  setInstrument,
} from 'merchant/reducers/instrumentRequests';
import { closeModal } from 'merchant_common/reducers/modals';

import Spinner from 'common/ui/Spinner';

import { bindActionCreators } from 'redux';

import {
  WEBSITE_DETAILS,
  MERCHANT_DOCUMENTS,
  MERCHANT_DETAILS,
  ACTION_REQUIRED,
  REJECTED,
} from '../../constants';

const tabTitle = {
  [WEBSITE_DETAILS]: 'Website Clarifications',
  [MERCHANT_DOCUMENTS]: 'Document Clarifications',
  [MERCHANT_DETAILS]: 'Other Clarifications',
};

const config = {
  [ACTION_REQUIRED]: {
    showInput: true,
    header: 'Clarifications needed',
    title: 'Update Information',
    subtitle: 'Provide clarifications & submit form to activate method',
  },
  [REJECTED]: {
    showInput: false,
    header: 'Discrepancies',
    title: 'Update Information',
    subtitle: 'These are the reasons because of which the request has been rejected',
  },
};

const ClarificationInput = (props) => {
  const {
    iirDiscrepancyId,
    label,
    fileUpload,
    handleFileChange,
    onTextChange,
    formFields,
    selectedFile,
    answer,
    setFiles,
    files,
    status,
  } = props;
  const [fileRemoved, setFileRemoved] = useState(false);

  const removeFile = (iirId) => {
    const tempFiles = files;
    delete tempFiles[iirId];
    setFiles(tempFiles);
    setFileRemoved(true);
  };

  const discrepancyAnswer = answer ? answer : formFields[iirDiscrepancyId]?.answer_field_value;

  return (
    <div className="clarification-input">
      <i className="i i-info-circle" />
      <div className="input-container">
        <p className="input-label">{label}</p>
        {config[status].showInput && (
          <>
            <div className="textarea-container">
              <textarea
                disabled={answer?.length}
                name={iirDiscrepancyId}
                id="clarification-textarea"
                placeholder="Sample text or whatever the merchant wants to add here ."
                onChange={onTextChange}
                value={discrepancyAnswer}
                maxLength="500"
              />

              {fileUpload && <i className="i i-file-attach" />}
            </div>
            {fileUpload && (
              <div className="file-upload">
                <label className="upload-label">Upload File</label>
                <FileUpload
                  onCloseClick={() => removeFile(iirDiscrepancyId)}
                  files={fileRemoved ? [] : selectedFile && [selectedFile]}
                  onFileChange={handleFileChange}
                  name="clarification-file-upload"
                  id="clarification-file-upload"
                  accept={['pdf', 'jpg', 'jpeg']}
                  maxSize={1048576} // 1MB
                />
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

const Clarifications = (props) => {
  const [selectedTab, setSelectedTab] = useState('');
  const [filteredTabs, setFilteredTabs] = useState([]);
  const [formFields, setFormFields] = useState([]);
  const [isDisabled, setIsDisabled] = useState(false);
  const [files, setFiles] = useState({});
  const [clarifications, setClarifications] = useState(false);

  const {
    onCloseClick,
    mirId,
    merchantDiscrepancies,
    discrepancyCategories,
    loading,
    status,
  } = props;

  const lastTab = filteredTabs.length - 1;

  async function fetchIirDiscrepancies() {
    await props.getIirDiscrepancies(mirId);
  }

  useEffect(() => {
    fetchIirDiscrepancies();
    return () => {
      setClarifications(false);
    };
  }, []);

  useEffect(() => {
    if (merchantDiscrepancies) {
      const cl = merchantDiscrepancies.map((m) => {
        return Object.assign(
          {},
          ...m,
          ...discrepancyCategories.filter((d) => d.discrepancy_id === m.discrepancy_id),
        );
      });
      setClarifications(cl);
      if (cl.length > 0) {
        const tabs = Array.from(new Set(cl?.map(({ category }) => category))).filter(Boolean);
        if (JSON.stringify(tabs) !== JSON.stringify(filteredTabs)) {
          setFilteredTabs(tabs);
        }
        setSelectedTab(tabs[0]);
      }
    }
  }, [merchantDiscrepancies]);

  useEffect(() => {
    const fields = {};
    if (clarifications?.length > 0) {
      clarifications.forEach(({ iir_discrepancy_id, iir_discrepancy_answer }) => {
        fields[iir_discrepancy_id] = {
          answer_field_value: iir_discrepancy_answer?.answer_field_value,
        };
      });
    }
    if (JSON.stringify(formFields) !== JSON.stringify(fields)) {
      setFormFields(fields);
    }
  }, [clarifications]);

  useEffect(() => {
    const isValid = Object.values(formFields).every(
      ({ answer_field_value }) => answer_field_value.length,
    );
    return selectedTab === filteredTabs[lastTab] && !isValid
      ? setIsDisabled(true)
      : setIsDisabled(false);
  }, [formFields, selectedTab, filteredTabs]);

  const handleFileChange = (uploadedFile, iirId) => {
    setFiles({ ...files, [iirId]: uploadedFile });
  };
  const submitDiscrepancies = async (data, filesUploaded) => {
    //required file object as files0: file, files1: file
    const formData = new FormData();
    Object.keys(filesUploaded).forEach((fileId) => {
      data.forEach((item, fileIndex) => {
        if (item.iir_discrepancy_id === fileId) {
          const filename = `files${fileIndex}`;
          formData.append(filename, filesUploaded[fileId]);
        }
      });
    });
    formData.append('data', JSON.stringify(data));
    await merchantFetch({
      url: `terminals/proxy/iir_discrepancy_answers`,
      method: 'post',
      data: formData,
    });
    props.fetchMerchantInstruments().then(() => props.setInstrument(props.instrument));
    props.closeModal();
  };

  const onSubmitDiscrepancyForm = (event) => {
    event.preventDefault();
    if (selectedTab === filteredTabs[lastTab]) {
      const data = [];
      for (const [key, value] of Object.entries(formFields)) {
        const entry = {
          iir_discrepancy_id: key,
          answer_field_value: value.answer_field_value,
          // answer_document: value.answer_document,
        };
        data.push(entry);
      }
      return submitDiscrepancies(data, files, formFields);
    } else {
      // sequence order is 0, 1 ,2. This logic is for Next button functionality
      return selectedTab === filteredTabs[0]
        ? setSelectedTab(filteredTabs[1])
        : selectedTab === filteredTabs[1]
        ? setSelectedTab(filteredTabs[2])
        : setSelectedTab(filteredTabs[0]);
    }
  };

  const onTextChange = (event) => {
    const { value, name } = event.target;
    return setFormFields({
      ...formFields,
      [name]: {
        ...formFields[name],
        answer_field_value: value,
      },
    });
  };

  const filteredClarifications =
    clarifications &&
    clarifications.filter(({ category }) => {
      return category === selectedTab;
    });

  return (
    <div className="container">
      <div className="sidebar">
        <div className="header">
          <h3>{config[status].title}</h3>
          <p className="subtitle">{config[status].subtitle}</p>
        </div>
        {!loading && clarifications && (
          <ul>
            {filteredTabs?.map((tab) => {
              const isActive = tab === selectedTab;
              return (
                <a
                  key={tab}
                  onClick={() => setSelectedTab(tab)}
                  className={isActive ? 'active' : 'inactive'}
                >
                  <li>{tab && tabTitle[tab]}</li>
                  {isActive && <i className="i i-chevron-right" />}
                </a>
              );
            })}
          </ul>
        )}
      </div>
      <div className="form-container">
        <ModalHeader title={config[status].header} onCloseClick={onCloseClick} />
        {!loading && clarifications && clarifications.length > 0 ? (
          <form>
            <div className="form">
              {filteredClarifications.map(
                ({ discrepancy_comment, iir_discrepancy_id, iir_discrepancy_answer }) => {
                  return (
                    <ClarificationInput
                      answer={iir_discrepancy_answer?.answer_field_value}
                      iirDiscrepancyId={iir_discrepancy_id}
                      handleFileChange={(file, _) => handleFileChange(file, iir_discrepancy_id)}
                      label={discrepancy_comment}
                      key={iir_discrepancy_id}
                      fileUpload={true}
                      onTextChange={onTextChange}
                      formFields={formFields}
                      setFiles={setFiles}
                      files={files}
                      status={status}
                    />
                  );
                },
              )}
            </div>

            {status === ACTION_REQUIRED && (
              <div className="footer">
                <button
                  type="submit"
                  className="Button--primary Button"
                  onClick={onSubmitDiscrepancyForm}
                  disabled={isDisabled}
                >
                  {selectedTab === filteredTabs[lastTab] ? (
                    <span>
                      Submit Form <i className="i i-chevron-right" />
                    </span>
                  ) : (
                    <span>Next</span>
                  )}
                </button>
              </div>
            )}
          </form>
        ) : (
          <Spinner />
        )}
      </div>
    </div>
  );
};

const mapStateToProps = (state) => {
  return {
    leafInstrument: state.instrumentRequests.leafInstrument,
    loading: state.instrumentRequests.loading,
    merchantDiscrepancies: state.instrumentRequests.merchantDiscrepancies,
    discrepancyCategories: state.instrumentRequests.discrepancyCategories,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      getIirDiscrepancies,
      fetchMerchantInstruments,
      closeModal,
      setInstrument,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(Clarifications);
