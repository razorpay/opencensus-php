import React, { useEffect, useState } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import { compose } from 'redux';
import SettingsApiKeys from 'merchant/views/Settings/Keys/List';
import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';

const ApiKeysWithRouter = withRouter(SettingsApiKeys);

const ApiKeys = ({ fetchAddWebsiteWorkflowStatus: fetchWebsiteWorkflowStatus }) => {
  const [isWebsiteInWorkflow, setIsWebsiteWorkflow] = useState(false);

  useEffect(() => {
    fetchWebsiteWorkflowStatus().then(({ data }) => {
      setIsWebsiteWorkflow(data);
    });
  }, []);

  return (
    <div className="api-keys-container">
      <ApiKeysWithRouter
        isWebsiteInWorkflow={isWebsiteInWorkflow}
        onWebsiteAdd={() => setIsWebsiteWorkflow(true)}
      />
    </div>
  );
};

export default compose(
  connect(null, {
    fetchAddWebsiteWorkflowStatus,
  }),
)(ApiKeys);
