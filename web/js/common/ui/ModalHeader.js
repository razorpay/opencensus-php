import PropTypes from 'prop-types';
import { connect } from 'react-redux';

const ModalHeader = (props) => (
  <div class="modal-header">
    {props.onCloseClick && (
      <button type="button" class="close" onClick={props.onCloseClick}>
        <i class="i i-close" />
      </button>
    )}
    {props.isCovidDonations === true ? (
      <div class="covid__donations">
        {props.user.isFeatureEnabled('covid_19_relief') ? (
          <i class="i i-done" style={{ color: '#1F890E' }} />
        ) : (
          <i class="i i-Donate" />
        )}{' '}
        <h3 class={`modal-title ${props.extraClass}`}>{props.title}</h3>
      </div>
    ) : (
      <h3 class={`modal-title ${props.extraClass}`}>{props.title}</h3>
    )}
  </div>
);

ModalHeader.propTypes = {
  title: PropTypes.oneOfType([PropTypes.string.isRequired, PropTypes.node.isRequired]),
  onCloseClick: PropTypes.func,
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, null)(ModalHeader);
