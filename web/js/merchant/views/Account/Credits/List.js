import { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { fetchCreditBalance } from 'merchant/reducers/credits';
import * as ModalActions from 'merchant_common/reducers/modals';
import CreditsDetails from 'merchant/views/Account/Credits/components';
import gaTrack from './ga';

function CreditsListContainer(props) {
  useEffect(() => {
    if (props.user.current) {
      props.fetchCreditBalance();
    }
  }, []);

  return <CreditsDetails user={props.user} {...props.credits} openModal={props.openModal} />;
}

const mapStateToProps = (state) => {
  return {
    credits: state.credits,
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      fetchCreditBalance,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(CreditsListContainer);
