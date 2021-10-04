import React, { useMemo } from 'react';
import Await, { usePromise } from '../../components/Await';
import LoansCollectionsContainer from './LoansCollectionsContainer';
import { fetchLoanData } from './util';

function LoansCollections() {
  const allData = usePromise(useMemo(() => fetchLoanData(), []));

  const onRefresh = () => {
    allData.setPromise(fetchLoanData());
  };

  return (
    <Await promise={allData}>
      <LoansCollectionsContainer loanData={allData.value} onRefresh={onRefresh} />
    </Await>
  );
}

export default LoansCollections;
