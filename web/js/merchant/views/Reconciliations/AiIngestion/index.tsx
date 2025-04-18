import React, { useState } from 'react';
import {
  Box,
  Card,
  CardBody,
  Heading,
  ProgressBar,
  Text,
  Divider,
} from '@razorpay/blade/components';

import AiRunDashboard from 'merchant/views/Reconciliations/AiIngestion/AiRunDashboard';
import EditProcessName from 'merchant/views/Reconciliations/AiIngestion/EditProcessName';
import SourceMapping from 'merchant/views/Reconciliations/AiIngestion/SourceMapping';
import UploadSourceAndPreview from 'merchant/views/Reconciliations/AiIngestion/UploadSourceAndPreview';

import type {
  Sources,
  AiIngestionStages,
  MlConfigIds,
} from 'merchant/views/Reconciliations/AiIngestion/types';

const AiIngestion = () => {
  const [isEditingProcessName, setIsEditingProcessName] = useState(false);
  const [processName, setProcessName] = useState<string>('');

  const [sourcesData, setSourcesData] = useState<Sources[]>([]);

  const [aiIngestionStages, setAiIngestionStages] = useState<AiIngestionStages>({
    'Add Sources': false,
    Mapping: false,
    Processing: false,
  });

  const [mlConfigIds, setMlConfigIds] = useState<MlConfigIds>({
    sessionId: '',
    auditLogId: '',
  });

  const [isGoBackClicked, setIsGoBackClicked] = useState(false);

  return (
    <Box paddingTop="spacing.1">
      <Card margin="spacing.6">
        <CardBody>
          <Box
            display="flex"
            justifyContent="space-between"
            alignItems="end"
            paddingBottom="spacing.6"
          >
            <Box display="flex" flexDirection="column" gap="spacing.4">
              <Heading weight="semibold" color="surface.text.gray.normal" size="large">
                {aiIngestionStages['Add Sources'] && aiIngestionStages.Mapping
                  ? processName
                  : ' Create New Proceses'}
              </Heading>
              <Text weight="regular" color="surface.text.gray.muted" size="large">
                {aiIngestionStages['Add Sources'] && aiIngestionStages.Mapping
                  ? sourcesData.map((source) => source.name).join(', ') || ''
                  : ' Add your data, select source files, and let AI handle the reconciliation.'}
              </Text>
            </Box>
            <Box display="flex" gap="spacing.4">
              {Object.keys(aiIngestionStages).map((stage) => (
                <Box key={stage} width="110px">
                  <ProgressBar
                    label={stage}
                    value={aiIngestionStages[stage] ? 100 : 0}
                    variant="linear"
                    size="medium"
                    showPercentage={false}
                  />
                </Box>
              ))}
            </Box>
          </Box>
          <Divider dividerStyle="solid" />
          {aiIngestionStages['Add Sources'] && aiIngestionStages.Mapping ? null : (
            <EditProcessName
              isEditingProcessName={isEditingProcessName}
              processName={processName}
              aiIngestionStages={aiIngestionStages}
              setProcessName={setProcessName}
              setIsEditingProcessName={setIsEditingProcessName}
            />
          )}

          <Divider dividerStyle="solid" />
          <Box>
            {!aiIngestionStages['Add Sources'] &&
            !aiIngestionStages.Mapping &&
            !aiIngestionStages.Processing ? (
              <UploadSourceAndPreview
                sourcesData={sourcesData}
                processName={processName}
                setSourcesData={setSourcesData}
                isEditingProcessName={isEditingProcessName}
                setAiIngestionStages={setAiIngestionStages}
                setIsEditingProcessName={setIsEditingProcessName}
              />
            ) : null}
            {aiIngestionStages['Add Sources'] &&
            !aiIngestionStages.Mapping &&
            !aiIngestionStages.Processing ? (
              <SourceMapping
                sourcesData={sourcesData}
                processName={processName}
                mlConfigIds={mlConfigIds}
                isGoBackClicked={isGoBackClicked}
                setMlConfigIds={setMlConfigIds}
                setAiIngestionStages={setAiIngestionStages}
              />
            ) : null}
            {aiIngestionStages['Add Sources'] && aiIngestionStages.Mapping ? (
              <AiRunDashboard
                mlConfigIds={mlConfigIds}
                setIsGoBackClicked={setIsGoBackClicked}
                setAiIngestionStages={setAiIngestionStages}
              />
            ) : null}
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
};

export default AiIngestion;
