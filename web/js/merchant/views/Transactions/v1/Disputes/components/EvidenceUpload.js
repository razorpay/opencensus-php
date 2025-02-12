import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { PowerSelect } from 'react-power-select';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import { analyticsTrack } from 'common/utils/analytics';
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import FileUpload from 'merchant/components/File/Upload';
import { merchantFetch } from 'merchant/utils/ajax';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

import AddOtherDoc from './AddOtherDoc';

const EvidenceUpload = (props) => {
  const { user, dispute, saveAsDraft, showNotification, fileTypes, canUserTakeAction } = props;
  const [docTypes, setDocTypes] = useState(fileTypes.filter((item) => item.name !== 'others'));
  const [selectedFixedDocs, setSelectedFixedDocs] = useState([]);
  const [selectedOptionalDocs, setSelectedOptionalDocs] = useState([]);
  const splitz = useSplitzService();
  const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;

  const isOtherFile = (docType) => docType === 'others';

  useEffect(() => {
    if (dispute.evidence) {
      const fixedFileTypes = fileTypes.filter((file) => file.name !== 'others');

      const uploadedFixedFiles = fixedFileTypes
        .filter((file) => !!dispute?.evidence?.[file.name])
        .map((file) => ({
          name: file.name,
          label: titleCase(file.name),
          docId: dispute?.evidence?.[file.name],
          docType: '',
        }));
      setSelectedFixedDocs(uploadedFixedFiles);

      if (dispute?.evidence?.others) {
        const uploadedOtherFile = (dispute?.evidence?.others || []).map((file) => ({
          name: file.type,
          label: titleCase(file.type),
          docId: file.document_ids,
          docType: 'others',
        }));
        setSelectedOptionalDocs(uploadedOtherFile);
      }
      const restDocs = fileTypes.filter((f) => f.name !== 'others' && !dispute?.evidence?.[f.name]);
      setDocTypes(restDocs);
    }
  }, [dispute.evidence, fileTypes]);

  const addDocument = (event) => {
    if (!event.option) return;
    const { name, label, docType = '' } = event.option;

    if (isOtherFile(docType)) {
      setSelectedOptionalDocs((docs) => [...docs, { name, label, docType }]);
    } else {
      setSelectedFixedDocs((docs) => [...docs, { name, label, docType }]);
      setDocTypes((docs) => docs.filter((file) => file.name !== name));
    }
  };

  const removeDocument = (fileName, docType) => {
    if (!canUserTakeAction) {
      return null;
    }

    let data;

    // remove file from server
    if (isOtherFile(docType)) {
      const updatedDocs = (selectedOptionalDocs || [])
        .filter((doc) => doc.name !== fileName && !!doc.docId)
        .map((doc) => ({
          type: doc.name,
          document_ids: doc.docId,
        }));

      data = {
        others: updatedDocs?.length !== 0 ? updatedDocs : null,
      };

      saveAsDraft({ others: null }).then((_) => {
        saveAsDraft(data).then((_) => {
          setSelectedOptionalDocs(updatedDocs);
        });
      });
    } else {
      // Adding fixed doc type back
      setDocTypes((files) => files.concat(fileTypes.filter((f) => f.name === fileName)));

      setSelectedFixedDocs((docs) => docs.filter((item) => item.name !== fileName));
      data = {
        [fileName]: null,
      };
      saveAsDraft(data);
    }
    return null;
  };

  const uploadFile = (file, progressTracker, fileType, docType) => {
    if (file) {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('purpose', 'dispute_evidence');

      analyticsTrack({
        objectName: 'dispute presentment',
        actionName: `add ${titleCase(isOtherFile(docType) ? 'others' : fileType)}`,
        screen: 'disputes',
        properties: {
          timestamp: Date.now(),
          version,
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });

      return merchantFetch({
        url: 'documents',
        method: 'POST',
        data: formData,
        onUploadProgress: progressTracker,
      })
        .then((res) => {
          if (res.data.id) {
            let data;

            if (isOtherFile(docType)) {
              const previousFiles = (selectedOptionalDocs || [])
                .filter((doc) => !!doc.docId)
                .map((doc) => ({
                  type: doc.name,
                  document_ids: doc.docId,
                }));

              data = {
                others: [...previousFiles, { type: fileType, document_ids: [res.data.id] }],
              };
            } else {
              data = { [fileType]: [res.data.id] };
            }
            saveAsDraft(data);
          }
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: err.errors || err,
          });
        });
    }
    return null;
  };

  const documents = [...selectedFixedDocs, ...selectedOptionalDocs].map((doc) => (
    <DismissableFileInput
      key={doc.name}
      name={doc.name}
      label={doc.label}
      docType={doc.docType}
      defaultValue={doc.docId}
      onFileChange={uploadFile}
      onFileRemove={(fileName) => removeDocument(fileName, doc.docType)}
      showNotification={showNotification}
      disputeStatus={dispute.status}
      canUserTakeAction={canUserTakeAction}
    />
  ));

  return (
    <div className="evidence-upload">
      {documents}
      {dispute.status === 'open' && canUserTakeAction && (
        <EntityDetailRow label="Add Document">
          <PowerSelect
            options={docTypes}
            optionLabelPath="label"
            searchEnabled={false}
            className="evidence-option"
            placeholder="--Select evidence type--"
            beforeOptionsTxt="Select evidence type"
            optionComponent={({ option }) => <div className="option">{option.label}</div>}
            afterOptionsComponent={({ select }) => {
              return (
                <AddOtherDoc
                  onActionClick={(name) => {
                    addDocument({
                      option: { label: name, name: name.replaceAll(' ', '_'), docType: 'others' },
                    });
                    select.actions.close();
                  }}
                />
              );
            }}
            onChange={addDocument}
          />
        </EntityDetailRow>
      )}
    </div>
  );
};

const DismissableFileInput = ({
  key,
  label,
  name,
  onFileChange,
  onFileRemove,
  showNotification,
  disputeStatus,
  canUserTakeAction,
  docType,
  ...rest
}) => {
  return (
    <div key={`${key}-${rest.defaultValue?.[0]}`} className="remove-wrapper">
      <EntityDetailRow label={<strong>{label}</strong>}>
        <FileUpload
          name={name}
          accept={['jpg', 'png', 'pdf']}
          maxSize={2102000}
          showCloseBtn={disputeStatus === 'open' && canUserTakeAction}
          showFileSize={false}
          showAcceptInfo={false}
          showStagedFileStatus={true}
          dropZoneCavityClassName="evidence"
          onBiggerFileSize={() => {
            showNotification({
              type: 'error',
              message: `Document too large. Max limit 2MB`,
            });
          }}
          onFileChange={(file, progressTracker) =>
            onFileChange(file, progressTracker, name, docType)
          }
          onCloseClick={() => onFileRemove(name)}
          fileName={
            <span
              onClick={() => {
                merchantFetch(`documents/${rest.defaultValue?.[0]}`)
                  .then((res) => {
                    if (res?.data.url) {
                      window.open(res.data.url);
                    }
                  })
                  .catch((err) => {
                    showNotification({
                      type: 'error',
                      message: err?.errors || err,
                    });
                  });
              }}
            >
              Click here to view file
            </span>
          }
          {...rest}
        />
        {!rest.defaultValue && (
          <span className="remove-file icon i-close" onClick={() => onFileRemove(name)} />
        )}
      </EntityDetailRow>
    </div>
  );
};

EvidenceUpload.propTypes = {
  dispute: PropTypes.object.isRequired,
  saveAsDraft: PropTypes.func.isRequired,
  showNotification: PropTypes.func.isRequired,
  fileTypes: PropTypes.array.isRequired,
  canUserTakeAction: PropTypes.bool,
};

export default connect(
  (state) => ({ fileTypes: state.dispute.fileTypes, user: state.session.user }),
  null,
)(EvidenceUpload);
