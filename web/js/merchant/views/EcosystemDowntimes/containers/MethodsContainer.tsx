import React, { useContext, useEffect, useMemo, useState } from 'react';
import Instrument from 'merchant/views/EcosystemDowntimes/components/Instrument';
import EcosystemHealthLoader from 'merchant/views/EcosystemDowntimes/components/EcosystemHealthLoader';
import { EcosystemDowntimeContext } from 'merchant/views/EcosystemDowntimes/context';
import {
  MethodName,
  MethodsGrid,
  MethodsListContainer,
  MethodsContainerStyled,
} from 'merchant/views/EcosystemDowntimes/styles';
import { getInstrumentList } from 'merchant/views/EcosystemDowntimes/helpers';
import { titleCase } from 'common/utils/rzp-utils';
import {
  ecosystemHealthPageView,
  trackEcosystemDowntimeEvents,
} from 'merchant/views/EcosystemDowntimes/events';
import EcosystemOverallSummary from 'merchant/views/EcosystemDowntimes/components/EcosystemOverallSummary';
import { ACTIONS, METHOD_NAMES_MAP } from 'merchant/views/EcosystemDowntimes/constants';
import EcosystemMethodSummary from 'merchant/views/EcosystemDowntimes/components/EcosystemMethodSummary';
import EcosystemHealthError from 'merchant/views/EcosystemDowntimes/components/EcosystemHealthError';
import { Text, Heading } from '@razorpay/blade/components';
import DowntimeDetailsContainer from './DowntimeDetailsContainer';
import { Modal, ModalBody } from 'common/components/Modal';

const MethodsContainer = (): JSX.Element => {
  const instrumentMap = useMemo(() => getInstrumentList(), []);
  const { state, dispatch } = useContext(EcosystemDowntimeContext);
  const [isDetailsOpen, setIsDetailsOpen] = useState<boolean>(false);
  const {
    activeDowntimes,
    isOngoingDowntimeLoading: isLoading,
    isOngoingDowntimeFetching: isFetching,
    isOngoingDowntimesError,
    isPreviousDowntimesError,
  } = state;

  const isError = isOngoingDowntimesError || isPreviousDowntimesError;

  useEffect(() => {
    trackEcosystemDowntimeEvents(ecosystemHealthPageView());
  }, []);

  if (isError && !isFetching) return <EcosystemHealthError />;

  const handleInstrumentClick = (instrument) => {
    setIsDetailsOpen(true);
    dispatch({
      type: ACTIONS.SET_FOCUSED_INSTRUMENT,
      payload: {
        focusedInstrument: instrument,
      },
    });
  };

  const handleOnDetailsClose = () => {
    setIsDetailsOpen(false);
    dispatch({
      type: ACTIONS.SET_FOCUSED_INSTRUMENT,
      payload: {
        focusedInstrument: null,
      },
    });
  };

  const methods = Object.keys(instrumentMap || {});
  return isLoading || isFetching ? (
    <EcosystemHealthLoader />
  ) : (
    <MethodsContainerStyled>
      <EcosystemOverallSummary />
      <MethodsGrid>
        {methods.map((method) => {
          const groups = Object.keys(instrumentMap?.[method] || {});
          return (
            <div key={method}>
              <MethodName>
                <Heading size="small" type="normal" variant="regular" weight="bold">
                  {METHOD_NAMES_MAP?.[method] || titleCase(method)}
                </Heading>
              </MethodName>
              <EcosystemMethodSummary method={method} />
              {groups.map((group) => {
                const instruments = instrumentMap?.[method]?.[group];
                return (
                  <MethodsListContainer key={group}>
                    <ul>
                      <Text contrast="low" size="medium" type="normal" variant="body" weight="bold">
                        {titleCase(group)}
                      </Text>
                      {instruments.map((instrument) => (
                        <li role="listitem" key={`${method}_${instrument.key}`}>
                          <Instrument
                            instrument={{
                              ...instrument,
                              method,
                              group,
                            }}
                            status={activeDowntimes?.[method]?.[group]?.[instrument.key]}
                            onClick={handleInstrumentClick}
                          />
                        </li>
                      ))}
                    </ul>
                  </MethodsListContainer>
                );
              })}
            </div>
          );
        })}
      </MethodsGrid>
      <Modal isOpen={isDetailsOpen} onClose={handleOnDetailsClose} closeable={true}>
        <ModalBody>
          <DowntimeDetailsContainer />
        </ModalBody>
      </Modal>
    </MethodsContainerStyled>
  );
};

export default MethodsContainer;
