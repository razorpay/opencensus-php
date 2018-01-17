import TetherComponent from 'react-tether';

export default ({ children }) => {
  return (
    <TetherComponent
      target="tabbed-container > header"
      attachment="top right"
      targetAttachment="top right"
      offset="-10px 12px"
    >
      <div />
      {/* required by react-tether */}
      {children}
    </TetherComponent>
  );
};
