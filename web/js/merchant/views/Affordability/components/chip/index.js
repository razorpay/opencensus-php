import './chip-style.styl';

export const Chip = ({ text, type }) => {
  return <span className={`chip chip-${type}`}>{text}</span>;
};
