import styled from 'styled-components';
import { FullPageLoader } from 'common/components/Loader';

const MagicLoader = styled.div`
  & > div {
    z-index: 3;
  }
`;

const Loader = () => (
  <MagicLoader>
    <FullPageLoader />
  </MagicLoader>
);

export default Loader;
