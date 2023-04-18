import React, { useMemo } from 'react';
import DowntimeSummaryTile from './DowntimeSummaryTile';
import { DowntimeTilesContainer } from 'merchant/views/EcosystemDowntimes/styles';
import type {
  DowntimeMetaDataType,
  DowntimeSummaryFieldTypes,
} from 'merchant/views/EcosystemDowntimes/types';

type DowntimeTilesPropTypes = {
  activeDowntime: DowntimeMetaDataType;
  pastDowntimes: DowntimeMetaDataType[];
  summaryFields: DowntimeSummaryFieldTypes[];
  isMobile: boolean;
};

const DowntimeTiles = ({
  isMobile,
  activeDowntime,
  pastDowntimes,
  summaryFields,
}: DowntimeTilesPropTypes): JSX.Element => {
  const results = useMemo(() => {
    return summaryFields.map((field) => ({
      ...field,
      value: field.value?.({
        activeDowntimeForInstrument: activeDowntime,
        pastDowntimesForInstrument: pastDowntimes,
      }),
    }));
  }, [activeDowntime, pastDowntimes, summaryFields]);

  return (
    <DowntimeTilesContainer>
      {results.map(({ description, information, value }) => (
        <DowntimeSummaryTile
          key={description}
          description={description}
          subText={information}
          value={value}
          isMobile={isMobile}
        />
      ))}
    </DowntimeTilesContainer>
  );
};

export default DowntimeTiles;
