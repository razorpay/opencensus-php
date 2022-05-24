import PropTypes from 'prop-types';

export default function SegmentSearchItem(props) {
  const { text } = props;

  if (!text) {
    return null;
  }

  return <div>{text}</div>;
}

SegmentSearchItem.propTypes = {
  text: PropTypes.string.isRequired,
};
