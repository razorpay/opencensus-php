import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import React, { useState, useEffect } from 'react';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { merchantFetch } from 'merchant/utils/ajax';
import { PowerSelect } from 'react-power-select';
import FileUpload from 'merchant/components/File/Upload';
import AddOtherDoc from './AddOtherDoc';
import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

const EvidenceUpload = (props) => {
  const { dispute, saveAsDraft, showNotification, fileTypes } = props;
  const [docTypes, setDocTypes] = useState(fileTypes.filter((item) => item.name !== 'others'));
  const [userSelectedDocs, setUserSelectedDocs] = useState([]);

  useEffect(() => {
    if (dispute.evidence) {
      const uploadedEvidence = fileTypes.filter((f) => !!dispute?.evidence?.[f.name]);
      setUserSelectedDocs(uploadedEvidence);

      const restDocs = fileTypes.filter((f) => !dispute?.evidence?.[f.name]);
      setDocTypes(restDocs);
    }
  }, [dispute.evidence, fileTypes]);

  const addDocument = (event) => {
    if (!event.option) return;
    const { name, label } = event.option;

    setUserSelectedDocs([...userSelectedDocs, { name, label }]);

    setDocTypes((docs) => docs.filter((file) => file.name !== name));
  };

  const isOtherFile = (fileType) => fileTypes.filter((item) => item.name === fileType).length === 0;

  const removeDocument = (fileName) => {
    setUserSelectedDocs((docs) => docs.filter((d) => d.name !== fileName));

    // Adding doc type back
    setDocTypes((files) => files.concat(fileTypes.filter((f) => f.name === fileName)));

    let data;
    // remove file from server
    if (isOtherFile(fileName)) {
      const updatedDocs = dispute.evidence.others.filter((item) => item.type !== fileName);
      data = {
        others: updatedDocs.length !== 0 ? updatedDocs : null,
      };
      saveAsDraft({ others: null }).then((_) => {
        saveAsDraft(data);
      });
    } else {
      data = {
        [fileName]: null,
      };
      saveAsDraft(data);
    }
  };

  const uploadFile = (file, progressTracker, fileType) => {
    if (file) {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('purpose', 'dispute_evidence');

      analyticsTrack({
        objectName: 'dispute presentment',
        actionName: `add ${titleCase(isOtherFile(fileType) ? 'others' : fileType)}`,
        screen: 'disputes',
        properties: {
          timestamp: Date.now(),
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

            if (isOtherFile(fileType)) {
              data = { others: [{ type: fileType, document_ids: [res.data.id] }] };
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

  const documents = userSelectedDocs.map((doc) =>
    doc.name !== 'others' ? (
      <DismissableFileInput
        key={doc.name}
        name={doc.name}
        label={doc.label}
        defaultValue={dispute?.evidence?.[doc.name]}
        onFileChange={uploadFile}
        onFileRemove={removeDocument}
        showNotification={showNotification}
        disputeStatus={dispute.status}
      />
    ) : (
      dispute?.evidence?.others?.map((item, idx) => (
        <DismissableFileInput
          key={item.type}
          name={item.type}
          label={titleCase(item.type)}
          defaultValue={dispute?.evidence?.others[idx]}
          onFileChange={uploadFile}
          onFileRemove={removeDocument}
          showNotification={showNotification}
          disputeStatus={dispute.status}
        />
      ))
    ),
  );

  return (
    <div class="evidence-upload">
      {documents}
      {dispute.status === 'open' && (
        <EntityDetailRow label="Add Document">
          <PowerSelect
            options={docTypes}
            optionLabelPath="label"
            searchEnabled={false}
            className="evidence-option"
            placeholder="--Select evidence type--"
            beforeOptionsTxt="Select evidence type"
            optionComponent={({ option }) => <div class="option">{option.label}</div>}
            afterOptionsComponent={({ select }) => {
              return (
                <AddOtherDoc
                  onActionClick={(name) => {
                    addDocument({ option: { label: name, name: name.replaceAll(' ', '_') } });
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
  label,
  name,
  onFileChange,
  onFileRemove,
  showNotification,
  disputeStatus,
  ...rest
}) => {
  return (
    <div class="remove-wrapper">
      <EntityDetailRow label={<strong>{label}</strong>}>
        <FileUpload
          name={name}
          accept={['jpg', 'png', 'pdf']}
          maxSize={2102000}
          showCloseBtn={disputeStatus === 'open'}
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
          onFileChange={(file, progressTracker) => onFileChange(file, progressTracker, name)}
          onCloseClick={() => onFileRemove(name)}
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
};

export default connect((state) => ({ fileTypes: state.dispute.fileTypes }), null)(EvidenceUpload);
