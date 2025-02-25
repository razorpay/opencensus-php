import { useLocation } from 'react-router-dom';

const useLocationQuery = () => new URLSearchParams(useLocation().search);

export default useLocationQuery;
