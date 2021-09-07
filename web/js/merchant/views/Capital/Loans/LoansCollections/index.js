import React, { useMemo } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import Await, { usePromise } from '../../components/Await';
import LoansCollectionsContainer from './LoansCollectionsContainer';
import { fetchLoanData } from './util';
import NoPermission from './NoPermission';

function LoansCollections({ user }) {
  const allData = usePromise(useMemo(() => fetchLoanData(), []));

  const onRefresh = () => {
    allData.setPromise(fetchLoanData());
  };

  if (!user.isLoansCollectionsEnabled) return <NoPermission />;

  return (
    <Await promise={allData}>
      <LoansCollectionsContainer allData={allData.value} onRefresh={onRefresh} />
    </Await>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

LoansCollections.propTypes = {
  user: PropTypes.object.isRequired,
};

export default connect(mapStateToProps)(LoansCollections);
